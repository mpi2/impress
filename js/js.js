//color animations plugin by John Resig Dual licensed under the MIT or GPL Version 2 licenses http://jquery.org/license
//edited by AR to make compatible with jQuery 1.8+ - d.curCSS to d.css
(function(d){d.each(["backgroundColor","borderBottomColor","borderLeftColor","borderRightColor","borderTopColor","color","outlineColor"],function(f,e){d.fx.step[e]=function(g){if(!g.colorInit){g.start=c(g.elem,e);g.end=b(g.end);g.colorInit=true}g.elem.style[e]="rgb("+[Math.max(Math.min(parseInt((g.pos*(g.end[0]-g.start[0]))+g.start[0]),255),0),Math.max(Math.min(parseInt((g.pos*(g.end[1]-g.start[1]))+g.start[1]),255),0),Math.max(Math.min(parseInt((g.pos*(g.end[2]-g.start[2]))+g.start[2]),255),0)].join(",")+")"}});function b(f){var e;if(f&&f.constructor==Array&&f.length==3){return f}if(e=/rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(f)){return[parseInt(e[1]),parseInt(e[2]),parseInt(e[3])]}if(e=/rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(f)){return[parseFloat(e[1])*2.55,parseFloat(e[2])*2.55,parseFloat(e[3])*2.55]}if(e=/#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(f)){return[parseInt(e[1],16),parseInt(e[2],16),parseInt(e[3],16)]}if(e=/#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(f)){return[parseInt(e[1]+e[1],16),parseInt(e[2]+e[2],16),parseInt(e[3]+e[3],16)]}if(e=/rgba\(0, 0, 0, 0\)/.exec(f)){return a.transparent}return a[d.trim(f).toLowerCase()]}function c(g,e){var f;do{f=d.css(g,e);if(f!=""&&f!="transparent"||d.nodeName(g,"body")){break}e="backgroundColor"}while(g=g.parentNode);return b(f)}var a={aqua:[0,255,255],azure:[240,255,255],beige:[245,245,220],black:[0,0,0],blue:[0,0,255],brown:[165,42,42],cyan:[0,255,255],darkblue:[0,0,139],darkcyan:[0,139,139],darkgrey:[169,169,169],darkgreen:[0,100,0],darkkhaki:[189,183,107],darkmagenta:[139,0,139],darkolivegreen:[85,107,47],darkorange:[255,140,0],darkorchid:[153,50,204],darkred:[139,0,0],darksalmon:[233,150,122],darkviolet:[148,0,211],fuchsia:[255,0,255],gold:[255,215,0],green:[0,128,0],indigo:[75,0,130],khaki:[240,230,140],lightblue:[173,216,230],lightcyan:[224,255,255],lightgreen:[144,238,144],lightgrey:[211,211,211],lightpink:[255,182,193],lightyellow:[255,255,224],lime:[0,255,0],magenta:[255,0,255],maroon:[128,0,0],navy:[0,0,128],olive:[128,128,0],orange:[255,165,0],pink:[255,192,203],purple:[128,0,128],violet:[128,0,128],red:[255,0,0],silver:[192,192,192],white:[255,255,255],yellow:[255,255,0],transparent:[255,255,255]}})(jQuery);
//jQuery 1.9+ got rid of $.browser! Modified from older jQuery version, respecting same license
function browser() {
    var ua = navigator.userAgent.toLowerCase()
    var match = /(chrome)[ \/]([\w.]+)/.exec(ua) ||
            /(webkit)[ \/]([\w.]+)/.exec(ua) ||
            /(opera)(?:.*version|)[ \/]([\w.]+)/.exec(ua) ||
            /(msie) ([\w.]+)/.exec(ua) ||
            ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec(ua) ||
            [];

    return {
        browser: match[ 1 ] || "",
        version: match[ 2 ] || "0"
    };
}


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

//all my code now:


function escapeHTML(s) {
    return (new String(s)).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
$(document).ready(function(){
    var path, controller;
    if (/^https?:\/\/(localhost|127\.0\.0\.1|(www\.)?mousephenotype).*/.test(location.href)) {
        path = '/impress';
        controller = '/ajax';
    } else {
        path = '/impress';
        controller = '/ajax';
    }
    //enable ajax caching
    $.ajaxSetup({cache: true});
    /**
     * show autocomplete when they start typing
     */
    if ($('input#ontsearchbox').length) {
        $('input#ontsearchbox').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: path + controller + '/autocomplete',
                    data: {
                        maxRows: 25,
                        name_startsWith: request.term
                    },
                    success: function(data) {
                        response(
                            $.map(data, function(item) {
                                return item;
                            })
                        );
                    }
                })
            },
            minLength: 2,
            select: function(event, ui) {
                $("#searchterm_id").val(ui.item.value);
                $(this).val(ui.item.value);
                $(this).parent().submit();
            },
            open: function() {
                $(this).removeClass("ui-corner-all").addClass("ui-corner-top");
            },
            close: function() {
                $(this).removeClass("ui-corner-top").addClass("ui-corner-all");
            }
        })
        .data("autocomplete")._renderItem = function(ul, item) {
            return $("<li></li>")
                    .data("item.autocomplete", item)
                    .append($("<a></a>").text(item.value + " - " + item.label))
                    .appendTo(ul);
        };
    }
    /**
     * search ontologies when someone hits the submit button or enter
     */
    $('form#ontsearchform').submit(function(e) {
        e.preventDefault();
        var errmsg = "Please Enter a valid Ontology ID from the list presented";
        var timestart = new Date();
        var term = $('input#ontsearchbox').val(); //searchterm_id
        //alert(term);
        if (/^(MP|CHEBI|BSPO|CL|ENVO|GO|IMR|MA|PATO):([0-9]{5}|[0-9]{7,8})$/.test(term) === false) {
            alert(errmsg + ' (1)');
            return false;
        }
        $.ajax({
            url: path + controller + '/search',
            //dataType:'jsonp',
            data: {term: term},
            success: function(data) {
                if (data == "" || data == null || data.length == 0) {
                    $('#ontsearchblurb').html('<p>No results matched your search query: ' + escape(term).replace('%3A', ':') + '</p>');
                    $('#ontsearchresults').html('');
                } else {
                    $('#ontsearchblurb').html('<p>Your search for <var>"' + escape(term).replace('%3A', ':') + '"</var> returned ' + data.length + ' matches in <var>' + ((new Date()) - timestart) + 'ms</var></p>');
                    var colcount; //colcount can be 2 or 4 - MP searches contain 2 columns for mp id & term; the others contain 4 - entity id & term + quality id & term
                    var r = '';
                    //create data table
                    $.map(data, function(item) {
                        if (item.mp_id)
                            colcount = 2;
                        else
                            colcount = 4;
                        r += oview(item, path);
                    });
                    //headings for data table
                    var z = '<div class="ontresultitem">' + ohview(colcount) + '</div>';
                    $('#ontsearchresults').html(z + r);
                }
                $('.hidden').hide();
            }
        });
    });
    //ontology search header view 3
    function ohview(colcount) {
        var z = "";
        if (colcount == 2) {
            z += '<div class="pipeline_name header">Pipeline</div>';
            z += '<div class="procedure_name header">Procedure</div>';
            z += '<div class="parameter_name header">Parameter</div>';
            z += '<div class="mp_id header">Mouse Phenotype ID</div>';
            z += '<div class="mp_term header">Mouse Phenotype Term</div>';
        } else if (colcount == 4) {
            z += '<div class="pipeline_name2 header">Pipeline</div>';
            z += '<div class="procedure_name2 header">Procedure</div>';
            z += '<div class="parameter_name2 header">Parameter</div>';
            z += '<div class="entity_quality_header header">';
            z += '	<div class="entity_header">Entities</div>';
            z += '	<div class="quality_header">Qualities</div>';
            z += '</div>';
        }
        return z;
    }
    //ontology search results view 4
    function oview(item, path) {
        var x = '';
        x += '<div class="ontresultitem">';
        if (item.mp_id) {
            x += '<div class="pipeline_name"><a href="procedures/' + item.pipeline_id + '">' + escapeHTML(item.pipeline_name) + '</a></div>';
            if (item.sop_id == null)
                x += '<div class="procedure_name"><a href="javascript:alert(\'SOP Unavailable\')">' + escapeHTML(item.procedure_name) + '</a></div>';
            else
                x += '<div class="procedure_name"><a href="protocol/' + item.procedure_id + '">' + escapeHTML(item.procedure_name) + '</a></div>';
            x += '<div class="parameter_name"><a href="parameters/' + item.procedure_id + '">' + escapeHTML(item.parameter_name) + '</a></div>';
            x += '<div class="mp_id"><a href="parameterontologies/' + item.parameter_id + '/' + item.procedure_id + '">' + item.mp_id + '</a></div>';
            x += '<div class="mp_term"><a href="parameterontologies/' + item.parameter_id + '/' + item.procedure_id + '">' + escapeHTML(item.mp_term) + '</a></div>';
        } else {
            x += '<div class="pipeline_name2"><a href="procedures/' + item.pipeline_id + '">' + escapeHTML(item.pipeline_name) + '</a></div>';
            if (item.sop_id == null)
                x += '<div class="procedure_name2"><a href="javascript:alert(\'SOP Unavailable\')">' + escapeHTML(item.procedure_name) + '</a></div>';
            else
                x += '<div class="procedure_name2"><a href="protocol/' + item.procedure_id + '">' + escapeHTML(item.procedure_name) + '</a></div>';
            x += '<div class="parameter_name2"><a href="parameters/' + item.procedure_id + '">' + escapeHTML(item.parameter_name) + '</a></div>';
            item.entity1_id = (item.entity1_id) ? '[' + item.entity1_id + ']' : '&nbsp;';
            item.entity1_term = (item.entity1_term) ? escapeHTML(item.entity1_term) : '&nbsp;';
            item.entityEmpty = (item.entity1_id == '&nbsp;') ? ' hidden' : '';
            x += '<div class="entity_id2' + item.entityEmpty + '"><a href="parameterontologies/' + item.parameter_id + '/' + item.procedure_id + '">' + escapeHTML(item.entity1_term) + ' ' + item.entity1_id + '</a></div>';
            item.entity2_id = (item.entity2_id) ? '[' + item.entity2_id + ']' : '&nbsp;';
            item.entity2_term = (item.entity2_term) ? escapeHTML(item.entity2_term) : '&nbsp;';
            item.entityEmpty = (item.entity2_id == '&nbsp;') ? ' hidden' : '';
            x += '<div class="entity_id2' + item.entityEmpty + '"><a href="parameterontologies/' + item.parameter_id + '/' + item.procedure_id + '">' + escapeHTML(item.entity2_term) + ' ' + item.entity2_id + '</a></div>';
            item.entity3_id = (item.entity3_id) ? '[' + item.entity3_id + ']' : '&nbsp;';
            item.entity3_term = (item.entity3_term) ? escapeHTML(item.entity3_term) : '&nbsp;';
            item.entityEmpty = (item.entity3_id == '&nbsp;') ? ' hidden' : '';
            x += '<div class="entity_id2' + item.entityEmpty + '"><a href="parameterontologies/' + item.parameter_id + '/' + item.procedure_id + '">' + escapeHTML(item.entity3_term) + ' ' + item.entity3_id + '</a></div>';
            item.quality1_id = (item.quality1_id) ? '[' + item.quality1_id + ']' : '&nbsp;';
            item.quality1_term = (item.quality1_term) ? escapeHTML(item.quality1_term) : '&nbsp;';
            item.qualityEmpty = (item.quality1_id == '&nbsp;') ? ' hidden' : '';
            x += '<div class="quality_id2' + item.qualityEmpty + '"><a href="parameterontologies/' + item.parameter_id + '/' + item.procedure_id + '">' + escapeHTML(item.quality1_term) + ' ' + item.quality1_id + '</a></div>';
            item.quality2_id = (item.quality2_id) ? '[' + item.quality2_id + ']' : '&nbsp;';
            item.quality2_term = (item.quality2_term) ? escapeHTML(item.quality2_term) : '&nbsp;';
            item.qualityEmpty = (item.quality2_id == '&nbsp;') ? ' hidden' : '';
            x += '<div class="quality_id2' + item.qualityEmpty + '"><a href="parameterontologies/' + item.parameter_id + '/' + item.procedure_id + '">' + escapeHTML(item.quality2_term) + ' ' + item.quality2_id + '</a></div>';
        }
        x += '</div>';
        return x;
    }
    /**
     * toggle show/hide the eq tables in the procedure ontology page
     */
    $('a#toggledisplayeqs').click(function(e) {
        e.preventDefault();
        if (/show/i.test($(this).text()))
            $(this).text('Hide Entity-Quality annotations');
        else
            $(this).text('Show Entity-Quality annotations');
        $('.eqonttable').fadeToggle('slow');
    });
    //when the browser needs a hand
    $('map area:last-child').css('cursor', 'pointer');
    //admin confirm un/delete
    $('a.admindelete').click(function(e) {
        e.preventDefault();
        var c = confirm("Are you sure you want to delete this item?");
        if (c)
            window.location = $(this).attr('href');
        return c;
    });
    $('a.adminundelete').click(function(e) {
        e.preventDefault();
        var c = confirm("Are you sure you want to undelete this item?");
        if (c)
            window.location = $(this).attr('href');
        return c;
    });
    //admin disable submit button to prevent double submission
    $('#addeditform').submit(function() {
        $('#submit,#nvsubmit').attr('disabled', 'disabled');
        return true;
    });
    //flash message effect
    $('#flashsuccess').animate({backgroundColor: '#FFC469'}, 1500);
    $('#flashfailure').animate({backgroundColor: '#FFB7B7'}, 1500);
    $('#flashfailure,#flashsuccess').click(function(e) {
        $(this).hide();
    });
    //hide title tooltip for disabled items (annoying)
    $('textarea[disabled=disabled],input[disabled=disabled],select[disabled=disabled]').each(function() {
        $(this).removeAttr('title');
    });
    /**
     * toggle Expand/Collapse of Ontology Options
     */
    $('div.collapsed a').click(function(e) {
        e.preventDefault();
        if ('+ Expand' == $(this).text())
            $(this).text('- Collapse');
        else
            $(this).text('+ Expand');
        $(this).next('.collapsedOntologyOptions').toggle();
    });
    //latest release note on history page
    $('.actionevent.latestrelease').prepend($('<div class="latestreleasemessage">latest release</div>'));
    //some styling hacks for MSIE 6 - 8 because people still use it, unfortunately
    if (browser().browser == 'msie' && browser().version < 9) {
        $('span.multi:nth-child(even), #weektable tr:nth-child(even), .actionevent:nth-child(even)').css('backgroundColor', '#EEE');
        $('.actionevent:nth-child(odd)').css('backgroundColor', '#FCDAAA')
        $('.actionevent.latestrelease:nth-child(even)').css('backgroundColor', 'rgba(50, 90, 220, 0.4)').css('border', '3px outset rgba(0, 0, 200, 0.2)');
        $('.actionevent.latestrelease:nth-child(odd)').css('backgroundColor', 'rgba(50, 100, 200, 0.2)').css('border', '3px outset rgba(0, 0, 200, 0.2)');
        $('.actionevent:hover, .actionevent.actionreleased, .actionevent.latestrelease:hover').css('backgroundColor', '#FFC469');
    }
    /**
     * @param string ont_id id of input field that takes ontology id like MP:0000001
     * @param string ont_term id of input field that takes ontology term like Increased Strength
     */
    window.ontologysearch = function ontologysearch(ont_id, ont_term) {
        var ont_id   = (typeof ont_id == 'object')   ? ont_id   : $('#' + ont_id.replace(/^#?/, '')),
            ont_term = (typeof ont_term == 'object') ? ont_term : $('#' + ont_term.replace(/^#?/, ''));
        if (ont_id.length && ont_term.length) {
            $(ont_term.selector).autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: path + controller + '/autocomplete',
                        data: {
                            maxRows: 25,
                            name_startsWith: request.term
                        },
                        success: function(data) {
                            response(
                                $.map(data, function(item) {
                                    return item;
                                })
                            );
                        }
                    })
                },
                minLength: 2,
                select: function(event, ui) {
                    ont_id.val(ui.item.value);
                    ont_term.val(ui.item.label);
                    return false;
                },
                open: function() {
                    $(this).removeClass("ui-corner-all").addClass("ui-corner-top");
                },
                close: function() {
                    $(this).removeClass("ui-corner-top").addClass("ui-corner-all");
                }
            })
            .data("autocomplete")._renderItem = function(ul, item) {
                return $("<li></li>")
                        .data("item.autocomplete", item)
                        .append($("<a></a>").text(item.value + " - " + item.label))
                        .appendTo(ul);
            };
            $(ont_id.selector).autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: path + controller + '/autocomplete',
                        data: {
                            maxRows: 25,
                            name_startsWith: request.term
                        },
                        success: function(data) {
                            response(
                                $.map(data, function(item) {
                                    return item;
                                })
                            );
                        }
                    })
                },
                minLength: 2,
                select: function(event, ui) {
                    ont_id.val(ui.item.value);
                    ont_term.val(ui.item.label);
                    return false;
                },
                open: function() {
                    $(this).removeClass("ui-corner-all").addClass("ui-corner-top");
                },
                close: function() {
                    $(this).removeClass("ui-corner-top").addClass("ui-corner-all");
                }
            })
            .data("autocomplete")._renderItem = function(ul, item) {
                return $("<li></li>")
                        .data("item.autocomplete", item)
                        .append($("<a></a>").text(item.value + " - " + item.label))
                        .appendTo(ul);
            };
        }
    }
});
