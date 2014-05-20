<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 *
 * Copyright 2014 Medical Research Council Harwell.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

class Impress extends CI_Controller
{
    private $_controller = null;

    public function __construct()
    {
        parent::__construct();
        $this->_controller = $this->router->class;
    }

    /**
     * Homepage
     */
    public function index()
    {
        $content['content'] = $this->load->view('homepage', null, true);
        $content['title'] = ' - International Mouse Phenotyping Resource of Standardised Screens';
        $this->load->view('impress', $content);
    }
	
    /**
     * Display Protocol (including in PDF format)
     */
    public function displaySOP($procId = null, $pipId = null, $pdf = null)
    {
        $proc = new Procedure($procId, $pipId);
        $content = '';

        if ($proc->exists()) {
            $sop = $proc->getSOP();
            
            //A Protocol must have at least two sections before it will display
            //as a web page - otherwise it will go straight through the PDF route

            if ($sop->exists() && count($sop->getSections()) >= 2) {
                $content = $this->load->view(
                    'displaysopsimplemod',
                    array(
                        'sop' => $sop,
                        'proc' => $proc,
                        'pipelineId' => $proc->getPipelineId(),
                        'controller' => $this->_controller
                    ),
                    true
                );
            } else {
                $content = '<p>This Procedure does not currently have a Protocol Description.</p>'
                         . '<p>Click ' . anchor('parameters/' . $proc->getId() . '/'
                         . $proc->getPipelineId(), 'here') . ' to view the Parameters in this Procedure.</p>';
                $pdf = true;
            }

            if ( ! $pdf) {
                $this->load->view('impress', array('content' => $content, 'title' => 'Displaying Protocol ' . e($sop->getTitle())));
            } else {
                //A database SOP record still needs to exist for the pdf to show
                
                //If the SOP is already available as a file then just serve that
                $file = $this->config->item('pdfpath') . $proc->getItemKey() . '.pdf';
                if (file_exists($file)) {
                    // die(basename($file));
                    // header("Pragma: public");
                    // header("Expires: 0");
                    // header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                    // header("Cache-Control: public");
                    // header("Content-Description: File Transfer");
                    // header("Content-Transfer-Encoding: binary");
                    header('Content-type: application/pdf');
                    header('Content-Disposition: inline; filename="' . basename($file) . '"'); //inline|attachment
                    header('Content-Length: ' . filesize($file));
                    readfile($file);
                } else {
                    //else generate the pdf sop on the fly
//                    $this->_outputPDF_TCPDFSectioned($proc, $content);
//                    $this->_outputPDF_DOMPDF($proc, $content);
                    $this->_outputPDF_MPDF($proc, $content);
                }
            }
        } else {
            $content = '<p>Protocol not found. You may have entered this page through a malformed URL.</p>';
            $this->load->view('impress', array('content' => $content, 'title' => 'Displaying Protocol Error'));
        }
    }
    
    /**
     * Outputs the PDF using MPDF. Does PDF caching too!
     * @param Procedure $procedure
     * @param string $content The rendered partial HTML
     */
    private function _outputPDF_MPDF(Procedure $procedure, $content)
    {
        $cacheDir = rtrim($this->config->item('cache_path'), '/') . '/';
        $fileName = $procedure->getItemKey() . '.pdf';
        $pdfFile = $cacheDir . $fileName;
        $contentHash = md5($content);
        $recreateFile = true;
        
        //check cache
        if (file_exists($pdfFile))
        {
            //get hash key from pdf keywords metadata to check if latest version
            $matches = array();
            $fh = fopen($pdfFile, 'rb');
            while (false !== ($line = fgets($fh))) {
                if (preg_match('/^\/Keywords \((.*?)\)$/', $line, $matches)) {
                    $rawFileHash = (string)@$matches[1];
                    $fileHash = implode('', array_filter(str_split($rawFileHash), function($c){return preg_match('/^[a-z0-9]$/', $c);}));
                    if ($fileHash == $contentHash) {
                        $recreateFile = false;
                    } else {
                        fclose($fh);
                        @unlink($pdfFile);
                        $recreateFile = true;
                    }
                    break;
                }
            }
            if ($fh) {
                fclose($fh);
            }
        }
        
        if ($recreateFile) {
            define('_MPDF_TEMP_PATH', $cacheDir);
            $mpdf = new mPDF('utf-8', 'A4', null, null, 10, 10, 11, 11, 0, 0, 'P');
        
            //the pdf needs a full url for images so this line of code replaces the relative url with an absolute one
            $content = str_replace('src="/impress/images/', 'src="' . base_url() . 'images/', $content);
            //add line breaks between option values
            $content = preg_replace('/<span class="multi">(.*?)<\/span>/', '<span class="multi">$1</span><br>', $content);
            $content = preg_replace('/<\/span><br>[\s]*<\/div>/', '</span></div>', $content);
            $content = preg_replace('/<a href="#">\+ Expand<\/a>/', '', $content);
            //styling
            $head = '<head><style type="text/css">
                    h1,h2,h3{font-family:serif;}
                    ul#sopmenu li{font-family:sans-serif;}
                    table{font-size:80%;}
                    span.multi{display:block;font-size:80%;}
                    #soppdf,.collapsed a{display:none;}
                    .collapsedOntologyOptions{display:block;}
                    a{text-decoration:none;}
                    </style>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                    </head>';
            $content = doctype('html4-trans') . '<html>' . $head . '<body>' . $content . '</body></html>';

            $mpdf->SetCreator('IMPReSS');
            $mpdf->SetAuthor('IMPReSS');
            $mpdf->SetTitle("{$procedure->getItemName()} [{$procedure->getItemKey()}]");
            $mpdf->SetKeywords($contentHash);
            $mpdf->simpleTables = false;
            $mpdf->WriteHTML($content);
            $mpdf->Output($pdfFile, 'F'); //I=inline
        }
        
        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($fileName) . '"'); //inline|attachment
        header('Content-Length: ' . filesize($pdfFile));
        readfile($pdfFile);
    }
    
    /**
     * Outputs PDF using TCPDF using a section-by-section approach so it's
     * converted correctly giving it a whole document puts in massive spaces
     * between titles
     * @param Procedure $procedure
     * @deprecated
     */
    private function _outputPDF_TCPDFSectioned(Procedure $procedure)
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('International Mouse Phenotyping Consortium');
        $pdf->SetTitle($procedure->getSOP()->getTitle() . ' [' . $procedure->getItemKey() . ']');
        $pdf->SetSubject('IMPReSS Protocol');
        $pdf->setDocCreationTimestamp(time());
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('dejavusans', '', 10, '', true);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $style = "
            <style type=\"text/css\">
            h1,h2,h3{font-family:serif;}
            ul#sopmenu li{font-family:sans-serif;}
            table{font-size:80%;}
            th{font-weight:bold;white-space:nowrap;}
            #soppdf,.collapsed a{display:none;}
            .collapsedOntologyOptions,span.multi{margin:0;padding:5px 0;}
            a{text-decoration:none;}
            .parameterkey:before{content:'[';}
            .parameterkey:after{content:']';}
            span.multi:nth-child(even){background-color:#EEE;}
            h3 a{color:#000;}
            div#soppdf{display:none;}
            ul#sopmenu li a{color:#000;text-decoration:none;}
            div.expandable{margin:0;padding:0;}
            </style>";
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->setHeaderData(null, null, null, null, null, array(255, 255, 255));
        $pdf->writeHTML($style);
        
        
        $sop = $procedure->getSOP();
        
        $pdf->writeHTML('<h1>' . $sop->getTitle() . ' [' . $procedure->getItemKey() . ']</h1>');
        
        $sectionTitles = array_map(function($s){return $s->getSectionTitle()->getTitle();}, $sop->getSections());
        $sectionTitles[] = 'Parameters';
        $sectionTitles[] = 'Metadata';
        
        $content = '<br><ul id="sopmenu">';
        foreach ($sectionTitles as $title) {
            $content .= "<li>" . e($title) . "</li>\n";
        }
        $content .= '</ul><br>';
        
        $pdf->writeHTML($content);
        
        foreach ($sop->getSections() as $section) {
            $sectionTitle = $section->getSectionTitle()->getTitle();
            $s  = "<h3>" . dexss($sectionTitle) . "</h3>\n";
            $s .= $section->getSectionText(); //this really aught to be dexss'd but it breaks dodgy html
            $s .= '<br>';
            $s = str_replace('src="/impress/images/', 'src="' . base_url() . 'images/', $s);
            $pdf->writeHTML($s);
        }
        
        //add parameters and metadata section titles
        $measuredParams = array();
        $metadataParams = array();
        foreach ($procedure->getParameters() as $param) {
            if ($param->getType() == EParamType::METADATA) {
                $metadataParams[] = $param;
            } else {
                $measuredParams[] = $param;
            }
        }
        
        $pdf->writeHTML('<h3>Parameters</h3><br>');

        if (empty($measuredParams)) {
            $pdf->writeHTML('<p>This Procedure does not contain any Measured Parameters</p>');
        } else {
            $s = $this->load->view(
                'listparameterstable',
                array(
                    'params' => $measuredParams,
                    'procedureId' => $procedure->getId(),
                    'pipelineId' => $procedure->getPipelineId(),
                    'controller' => $this->_controller
                ),
                true
            );
            $s = preg_replace('/<span class="multi">(.*?)<\/span>/', '<span class="multi">$1</span><br><br>', $s);
            $s = preg_replace('/<br><br>\s+?<\/div>/', '</div>', $s);
            $s = preg_replace('/<a href="#">\+ Expand<\/a>/', '', $s);
            $pdf->writeHTML($s);
        }
        
        $pdf->writeHTML('<h3>Metadata</h3><br>');
        
        if (empty($metadataParams)) {
            $pdf->writeHTML('<p>This Procedure does not contain any Metadata Parameters</p>');
        } else {
            $s = $this->load->view(
                'listparameterstable',
                array(
                    'params' => $metadataParams,
                    'procedureId' => $procedure->getId(),
                    'pipelineId' => $procedure->getPipelineId(),
                    'controller' => $this->_controller
                ),
                true
            );
            $s = preg_replace('/<span class="multi">(.*?)<\/span>/', '<span class="multi">$1</span><br><br>', $s);
            $s = preg_replace('/<br><br>\s+?<\/div>/', '</div>', $s);
            $s = preg_replace('/<a href="#">\+ Expand<\/a>/', '', $s);
            $pdf->writeHTML($s);
        }
        
        $pdf->Output($procedure->getItemKey() . '.pdf', 'I');
    }

    /**
     * This outputs the PDF using TCPDF in one go
     * @see Impress::_outputPDF_TCPDFSectioned()
     * @param Procedure $procedure
     * @param string $content The rendered partial HTML
     * @deprecated
     */
    private function _outputPDF_TCPDF(Procedure $procedure, $content)
    {
        //the pdf needs a full url for images so this line of code replaces the relative url with an absolute one
        $content = str_replace('src="/impress/images/', 'src="' . base_url() . 'images/', $content);
        $content = preg_replace('/<span class="multi">(.*?)<\/span>/', '<span class="multi">$1</span><br><br>', $content);
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('International Mouse Phenotyping Consortium');
        $pdf->SetTitle($procedure->getSOP()->getTitle() . ' [' . $procedure->getItemKey() . ']');
        $pdf->SetSubject('IMPReSS Protocol');
        $pdf->setDocCreationTimestamp(time());
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('dejavusans', '', 10, '', true);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $style = "
            <style type=\"text/css\">
            h1,h2,h3{font-family:serif;}
            ul#sopmenu li{font-family:sans-serif;}
            table{font-size:80%;}
            th{font-weight:bold;white-space:nowrap;}
            #soppdf,.collapsed a{display:none;}
            .collapsedOntologyOptions,span.multi{margin:0;padding:5px 0;}
            a{text-decoration:none;}
            .parameterkey:before{content:'[';}
            .parameterkey:after{content:']';}
            span.multi:nth-child(even){background-color:#EEE;}
            h3 a{color:#000;}
            div#soppdf{display:none;}
            ul#sopmenu li a{color:#000;text-decoration:none;}
            div.expandable{margin:0;padding:0;}
            </style>";
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->setHeaderData(null, null, null, null, null, array(255, 255, 255));
        $pdf->writeHTML($style . $content);
        $pdf->Output($procedure->getItemKey() . '.pdf', 'I');
    }

    /**
     * This outputs the PDF using DOMPDF
     * @param Procedure $procedure
     * @param string $content The rendered partial HTML
     * @deprecated
     */
    private function _outputPDF_DOMPDF(Procedure $procedure, $content)
    {
        //the pdf needs a full url for images so this line of code replaces the relative url with an absolute one
        $content = str_replace('src="/impress/images/', 'src="' . base_url() . 'images/', $content);
        require_once APPPATH . 'third_party/dompdf/dompdf_config.inc.php';
        $dompdf = new DOMPDF();
        $head = '<head><style type="text/css">
            body{font-family:Cardo;}
            h1,h2,h3{font-family:serif;}
            ul#sopmenu li{font-family:sans-serif;}
            table{font-size:60%;}
            span.multi{display:block;font-size:50%;}
            #soppdf,.collapsed a{display:none;}
            .collapsedOntologyOptions{display:block}
            a{text-decoration:none;}
            </style>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            </head>'; //span.multi:nth-child(even){background-color:#EEE;}
        $dompdf->load_html(doctype('html4-trans') . '<html>' . $head . '<body>' . $content . '</body></html>');
        $dompdf->set_paper('letter', 'portrait');
        $dompdf->render();
        $dompdf->stream($procedure->getItemKey() . '.pdf', array('Attachment' => false));
    }

    /**
     * List all Pipelines in IMPReSS
     */
    public function pipelines()
    {
        $content  = '<h2>Listing All Pipelines</h2>';
        $content .= $this->load->view('listpipelines', array('pipelines' => PipelinesFetcher::getPipelines(), 'controller' => $this->_controller), true);
        $this->load->view('impress', array('content' => $content, 'title' => 'Displaying All Pipelines'));
    }

    /**
     * About page
     */
    public function about()
    {
        $this->load->view('impress', array('content' => '<p>IMPReSS is the new version of <a href="http://empress.har.mrc.ac.uk/" target="_blank">EMPReSS</a>.</p>', 'title'=>'About'));
    }

    /**
     * Contact page
     */
    public function contact()
    {
        $this->load->view('impress', array('content' => $this->load->view('contact', null, true), 'title' => 'Contact'));
    }

    /**
     * Copyright page
     */
    public function copyright()
    {
        $this->load->view('impress', array('title' => 'Copyright Statement', 'content' => $this->load->view('copyright', null, true)));
    }

    /**
     * View Procedures in a Pipeline
     */
    public function listProcedures($pipelineId = null)
    {
        $pip = new Pipeline($pipelineId);
        $content = '';

        if ($pip->exists()) {
            $content = '<h2>Phenotyping Protocols for Pipeline: ' . e($pip->getItemName())
                     . ' <span class="pipelinekey">' . $pip->getItemKey() . '</span></h2>' . PHP_EOL
                     . $this->load->view('listproceduresverticalmod', array('procs' => $pip->getProcedures(), 'pipelineId' => $pip->getId(), 'controller' => $this->_controller), true);
        } else {
            $content = '<p>An error occured. Pipeline Missing. You may have accessed this page through a malformed URL.</p>';
        }

        $this->load->view('impress', array('content' => $content, 'title' => 'Listing Procedures in ' . e($pip->getItemName())));
    }

    /**
     * View Parameters in a Procedure
     */
    public function listParameters($procedureId = null, $pipelineId = null)
    {
        $proc = new Procedure($procedureId, $pipelineId);
        $content = '';

        if ($proc->exists()) {
            $procTitle = anchor('protocol/' . $proc->getId() . '/' . $proc->getPipelineId(), e($proc->getItemName()));
            $content = '<h2>Parameters for Procedure: ' . $procTitle . ' <span class="procedurekey dark">' . $proc->getItemKey() . '</span></h2>';
            if ($proc->getWeek() != 0) //0=Unrestricted
                $content .= '<h3>Procedure carried out on ' . e($proc->getWeekLabel()) . '</h3>';

            $params = $proc->getParameters();

            if (empty($params)) {
                $content .= '<p>There are no parameters in this procedure.</p>';
            } else {
                //because some parameters have really long strings in them (usually in the "derived" column), it causes 
                //the parameters table to expand beyond the confines of the page so we need to set the table-layout as fixed width
                if (in_array($proc->getItemKey(), array(
                        //'IMPC_GRS_001',  //IMPC Grip Strength /83
                        //'ESLIM_022_001', //ESLIM Body Weight /2
                        'ESLIM_004_001', //ESLIM simplified IPGTT /5
                        'ESLIM_009_001', //ESLIM grip strength /12
                        'ESLIM_010_001', //ESLIM rotarod /13
                        'ESLIM_011_001', //ESLIM accoustic Startle&PPI /14
                        'GMC_926_001', //GMC rotarod /26
                        'GMC_914_001'   //GMC food efficiency /43
                    )
                )) {
                    $fixedsizetable = TRUE;
                } else {
                    $fixedsizetable = FALSE;
                }
                
                $content .= $this->load->view(
                    'listparameterstable',
                    array(
                        'params' => $params,
                        'procedureId' => $proc->getId(),
                        'pipelineId' => $pipelineId,
                        'controller' => $this->_controller,
                        'fixedsizetable' => $fixedsizetable
                    ),
                    true
                );
            }
        } else {
            $content = '<p>An error occured. Procedure Missing. You may have accessed this page through a malformed URL.</p>';
        }

        $this->load->view('impress', array('content' => $content, 'title' => 'Listing Parameters in Procedure ' . $proc->getItemName()));
    }

    /**
     * Display a Parameter's associated ontologies
     */
    public function listOntologies($parameterId = null, $procedureId = null, $pipelineId = null)
    {
        $proc = new Procedure($procedureId, $pipelineId);
        $procId = $proc->getId();
        $param = new Parameter($parameterId, $procId);
        $paramName = (empty($procId)) ? e($param->getItemName()) : anchor('parameters/' . $proc->getId() . '/' . $pipelineId, e($param->getItemName()));
        $paramName .= ' <span class="procedurekey dark">' . $param->getItemKey() . '</span>';
        $content = "<h2>Potential Ontology Annotations for Parameter: " . $paramName . "</h2>\n";

        if ($param->exists()) {

            /**
             * The view is split between two view files - one that handles MP
             * annotations and another which handles eq annotations.
             * Views:
             * 	- list MP Ontologies
             * 	- list EQ Ontologies
             */
            $content .= $this->load->view('listmpontologiestable', array('param' => $param, 'controller' => $this->_controller, 'hideemptytable' => true), true);
            $content .= '<br>' . $this->load->view('listeqontologiestable', array('param' => $param, 'controller' => $this->_controller, 'hideemptytable' => true), true);
        } else {
            $content = '<p>An error occured. Parameter Ontology Missing. You may have accessed this page through a malformed URL.</p>';
        }

        $this->load->view('impress', array('content' => $content, 'title' => 'View Ontologies in Parameter ' . e($param->getItemName())));
    }

    /**
     * Display Procedure Ontologies
     */
    public function displayProcedureOntologies($procedureId = null, $pipelineId = null)
    {
        $proc = new Procedure($procedureId, $pipelineId);
        $content = '<h1>Ontologies in Procedure: ' . e($proc->getItemName()) . ' '
                 . '<span class="procedurekey dark">' . $proc->getItemKey() . '</span></h1>'
                 . '<p><a href="#" id="toggledisplayeqs">Show Entity-Quality annotations</a></p>';
        
        if ($proc->exists())
        {
            $parameters = $proc->getParameters();
            
            if (count($parameters) == 0)
                $content .= '<p>There are no ontologies for this procedure</p>';
            
            foreach ($parameters as $param) {
                $content .= '<h3>Parameter: ' . e($param->getItemName()) . ' <span class="procedurekey dark">' . $param->getItemKey() . '</span></h3>';
                $content .= $this->load->view('listmpontologiestable', array('param' => $param, 'controller' => $this->_controller, 'hideemptytable' => TRUE, 'hidden' => FALSE), TRUE);
                $content .= '<br>' . $this->load->view('listeqontologiestable', array('param' => $param, 'controller' => $this->_controller, 'hidden' => TRUE), TRUE);
            }
        }
        else
        {
            $content = '<p>An error occured. Procedure missing. You may have accessed this page through a malformed URL.</p>';
        }

        $this->load->view('impress', array('content' => $content, 'title' => 'View Ontologies in Procedure ' . e($proc->getItemName())));
    }

    /**
     * Search Ontologies page
     */
    public function ontologies()
    {
        $content = $this->load->view('ontologysearch', null, true);
        $this->load->view('impress', array('content' => $content, 'title' => 'Search Ontologies in IMPReSS'));
    }
        
    /**
     * Display all Ontology Options for a Parameter
     */
    public function ontologyOptions($parameterId = null, $procedureId = null, $pipelineId = null)
    {
        $parameter = new Parameter($parameterId, $procedureId);
        $title = 'Ontology Options';
        $content = '';

        if ($parameter->exists()) {
            $ontologyOptions = array();
            foreach ($parameter->getOntologyGroups() as $ontologyGroup) {
                foreach ($ontologyGroup->getOntologyOptions() as $ontologyOption) {
                    $ontologyOptions[] = $ontologyOption;
                }
            }

            $title = 'Complete Ontology Options for Parameter: '
                   . anchor('parameters/' . $procedureId . '/' . $pipelineId, $parameter->getItemName())
                   . ' <span class="parameterkey dark">' . $parameter->getItemKey() . '</span>';
            $content = $this->load->view(
                'listontologyoptions',
                array(
                    'parameter' => $parameter,
                    'procedureId' => $parameter->getProcedureId(),
                    'pipelineId' => $pipelineId,
                    'controller' => $this->_controller,
                    'ontologyOptions' => $ontologyOptions
                ),
                true
            );
        } else {
            $content = '<p>An error occured. The parameter selected does not exist.</p>';
            ImpressLogger::log(ImpressLogger::WARNING, 'Parameter id does not exist');
        }

        $this->load->view('impress', array('content' => '<h2>' . $title . '</h2>' . $content, 'title' => $title));
    }

    /**
     * View Glossary of Terms
     */
    public function glossary() {
        $this->load->model('glossarymodel');
        $content = '<h1>Glossary of Terms</h1>' . PHP_EOL;
        foreach ($this->glossarymodel->fetchAll() as $item) {
            if ($item['deleted'] == 0) {
                $content .= '<div class="glossaryitem">';
                $content .= '<span class="glossaryterm"><a name="' . e($item['term']) . '">' . e($item['term']) . '</a></span>';
                $content .= '<span class="glossarydefinition">' . dexss($item['definition']) . '</span>';
                $content .= '</div>' . PHP_EOL;
            }
        }
        $this->load->view('impress', array('content' => $content, 'title' => 'IMPReSS Glossary of Terms'));
    }

    /**
     * View change history
     */
    public function displayChangeHistory($pipelineId = null, $procedureId = null)
    {
        $this->load->helper('form');
        $users = array();
        $result = $this->_getChangeHistory($pipelineId, $procedureId, $users);

        $content = $this->load->view(
            'changehistory',
            array(
                'pipelines' => PipelinesFetcher::getPipelines(),
                'selectedPipelineId' => (int) $pipelineId,
                'selectedProcedureId' => (int) $procedureId,
                'users' => $users,
                'result' => $result
            ),
            true
        );
        $this->load->view('impress', array('content' => $content, 'controller' => $this->_controller, 'title' => 'IMPReSS Change History'));
    }

    private function _getChangeHistory($pipeline = null, $procedure = null, array &$users = array())
    {
        $this->load->model('changelogmodel');

        //work out if server is on beta or live server to highlight the latest records
        if ($this->config->item('server') == 'beta')
            $releaseDate = $this->changelogmodel->getLatestBetaReleaseDate();
        else if ($this->config->item('server') == 'live')
            $releaseDate = $this->changelogmodel->getLatestLiveReleaseDate();
        else
            $releaseDate = '2000-01-01 00:00:00'; //a datetime that is not found in the db, so nothing is highlighted

        $limit = 10000;
        $logs = array();
        if (empty($pipeline) && empty($procedure)) {
            $logs = $this->changelogmodel->fetchAll($limit);
        } else if ( ! empty($pipeline) && empty($procedure)) {
            $logs = $this->changelogmodel->getByPipeline($pipeline, $limit);
        } else if ( ! empty($pipeline) && ! empty($procedure)) {
            $logs = $this->changelogmodel->getByPipelineAndProcedure($pipeline, $procedure, $limit);
        }
        foreach ($logs as $log) {
            $users[] = $log['username'];
        }
        $users = array_unique($users);
        return $this->load->view(
            'changelog',
            array(
                'logs' => $logs,
                'latestReleaseDate' => $releaseDate,
                'betaorlive' => ($this->config->item('server') == 'live') ? 'live' : 'beta',
                'controller' => $this->_controller
            ),
            true
        );
    }

    /**
     * This loads the change-history rows only and not the form and other stuff
     * above - it is used for dynamically loading the rows in an AHAH call
     */
    public function getChangeHistory($pipelineId = null, $procedureId = null)
    {
        echo $this->_getChangeHistory($pipelineId, $procedureId);
    }

    /**
     * View Procedure XML Example
     */
    public function procedureXML($procId = null, $pipId = null)
    {
        $pip = new Pipeline($pipId);
        $proc = new Procedure($procId, $pip->getId());
        
        if ($proc->exists() && $pip->exists())
        {
            $this->load->model('exampleprocedurexmlgeneratormodel', 'gen');
            $this->gen->setProcedure($proc);
            $this->gen->setPipeline($pip);
            header('Content-type: text/xml');
            echo $this->gen->generate();
        }
        else
        {
            echo 'An error occured. Failed to locate the procedure or pipeline.';
        }
    }

}
