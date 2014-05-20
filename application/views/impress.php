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

/**
 * @param string $title
 * @param string $content
 */

$controller = $this->router->default_controller . '/';

echo doctype('html4-trans') . PHP_EOL;
?>
<html>
<head>
<title>IMPReSS <?php echo (empty($title)) ? '' : e($title); ?></title>
<?php
echo meta(
    array(
        array('name' => 'robots', 'content' => 'follow'),
        array('name' => 'Content-type', 'content' => 'text/html;charset=utf-8', 'type' => 'equiv')
    )
);
echo link_tag('js/css/smoothness/jquery-ui.css') . PHP_EOL;
echo link_tag('css/screen.css') . PHP_EOL;
echo link_tag('favicon.ico', 'shortcut icon', 'image/ico') . PHP_EOL;

?>
<!--[if lt IE 8]>
<?php echo link_tag('css/ie7.css') . PHP_EOL; ?>
<![endif]-->
<script type="text/javascript" src="<?php echo base_url(); ?>js/js/jquery.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>js/js/jquery-ui.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>js/js.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>js/jquery.autoresize.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>js/jquery.expander.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    $('textarea').autoResize();
    $('span.expandable').expander({slicePoint:120});
    $('input,textarea,select').tooltip({position:{at:'right+30 top', my:'left top'}});
});
</script>
<?php if($this->config->item('analytics')) echo $this->config->item('analyticshead'); ?>
</head>
<body>
<div id="SiteWrapper">
    <div id="Banner">
        <a href="<?php echo base_url(); ?>"><img src="<?php echo base_url(); ?>images/impresslogo.gif" alt="logo IMPReSS International Mouse Phenotyping Resource of Standardised Screens"></a>
        <span>International Mouse Phenotyping Resource of Standardised Screens</span>
    </div>

    <ul id="menu">
    <img src="<?php echo base_url(); ?>images/menu_left4.jpg" class="PrimaryLeftIMG" alt="">
    <li>
        <?php echo anchor(null, 'Home', array('title' => 'Home Page')); ?>
    </li>
     <li>
      <?php echo anchor('pipelines', 'Pipelines', array('title' => 'Pipelines')); ?>
      <ul id="PipelinesDropdown">
        <?php
        //display pipelines
        $first = true;
        foreach(PipelinesFetcher::getPipelines() as $p){
            if ( ! should_display($p)) {
                continue;
            }
            
            if ($p->isDeprecated()) {
                echo '<li>' . anchor('pipelines#legacy', 'Legacy Pipelines', array('title' => 'Legacy Pipelines')) . '</li>';
                break;
            }
            
            echo "<li>\n";
            if($first) echo '<img class="corner_inset_left" alt="" src="' . base_url() . 'images/corner_inset_left4.png">';
            echo anchor('procedures/' . $p->getId(), e($p->getItemName()));
            if($first) echo '<img class="corner_inset_right" alt="" src="' . base_url() . 'images/corner_inset_right4.png">';
            echo "</li>\n";
            $first = false;
        }
        ?>
        <li class="last">
        <img class="corner_left" alt="" src="<?php echo base_url(); ?>images/corner_left4.png">
        <img class="middle" alt="" src="<?php echo base_url(); ?>images/DOT4.gif">
        <img class="corner_right" alt="" src="<?php echo base_url(); ?>images/corner_right4.png">
        </li>
      </ul>
     </li>
    <li>
        <?php echo anchor('ontologysearch', 'Ontology Search', array('title' => 'Ontology Search')); ?>
    </li>
    <li style="display:none">
        <?php echo anchor('about', 'About', array('title' => 'About IMPReSS')); ?>
    </li>
    <li>
        <?php echo anchor('glossary', 'Glossary', array('title' => 'Glossary of Terms')); ?>
    </li>
    <li>
        <?php echo anchor('https://www.mousephenotype.org/impress-help-documentation', 'Help', array('title' => 'Help Documentation', 'target' => '_blank')); ?>
    </li>
    <li>
        <?php echo anchor('contact', 'Contact Us', array('title' => 'Contact IMPReSS')); ?>
    </li>
    <li>
        <?php echo anchor($this->config->item('mousephenotypeurl'), 'Back to IMPC Website', array('title' => 'Back to IMPC Website')); ?>
    </li>
    <li id="searchbox" style="display:none;">
        <form method="get" action="#" name="searchboxform" id="searchboxform" onsubmit="return false;">
        <input type="text" name="search" id="searchboxtextbox" disabled="disabled">
        <input type="submit" name="searchboxsubmit" id="searchboxsubmit" value="Search">
        </form>
    </li>
    <img src="<?php echo base_url(); ?>images/menu_right4.jpg" class="PrimaryRightIMG" alt="">
    </ul>

    <div id="Content">
        <?php echo (empty($content)) ? '<p>Welcome to IMPReSS</p>' : $content; ?>
    </div>

    <div id="Footer">
        <p id="copyrightnotice" style="display:none"><?php echo anchor($controller . 'copyright', 'Copyright <span><!--/--></span>&copy; The Medical Research Council ' . date('Y')); ?></p>
        <div id="login" style="display:none"><?php /*
        if(User::isLoggedIn()){
            $userinfo = User::getUser();
            $admin = base_url() . 'admin';
            if( ! stristr(base_url(), 'localhost')){
                $this->load->helper('httpsify_url');
                $admin = httpsify_url(base_url() . 'admin');
            }
            echo 'User ' . htmlentities($userinfo['name']) . ' is logged in. ' . anchor($controller . 'logout', 'Log out') . ' &bull; ' . anchor($admin, 'Go to Admin');
        }
        else{
            echo anchor($controller . 'login', 'Log in');
        }
        */ ?>
        </div>
    </div>
</div>

<div id="thefinalfrontier"></div>

</body>
</html>
