<?php
require 'vendor/autoload.php';
require 'helperfunctions.php';

$xmlstub = file_get_contents ('sakai-help-contents-stub.xml');

$basepath = "/tmp/help/";
$svnpath = "/home/samo/dev/trunk-all/help/help/src";
$helpxml_file = file_get_contents ("sakai.help.xml");
$helpxml_file_svn = $svnpath . "/../../help-tool/src/webapp/tools/sakai.help.xml";

// This is the CSS we are going to include in each article
$sakai_css = '<link href="/library/skin/neo-default/tool.css" media="screen" rel="stylesheet" type="text/css" charset="utf-8" />';
$sakai_css .= '<link href="../css/neutral.css" media="screen" rel="stylesheet" type="text/css" />';

// Default QueryPath options use ISO-8859-1
$qp_options = array(
  'convert_from_encoding' => 'UTF-8',
  'convert_to_encoding' => 'UTF-8',
  'strip_low_ascii' => FALSE,
);

$instructor_file = "Sakai-10-Instructor-Guide.html";
$student_file = "Sakai-10-Student-Guide.html";

$instructor_xml = simplexml_load_string (file_get_contents ($basepath . $instructor_file));

$qp = qp ($instructor_xml, 'div#TOC');

$help_dirs = array();
foreach ($qp->children('div.chapter-container') AS $chapter) {
  $xmlcontents = simplexml_load_string($xmlstub);

  foreach ($chapter->branch()->children('h2') AS $chapter_h2) {
    $chapter_title = $chapter_h2->text();
    $chapter_id = escape_for_id ($chapter_title);
    $destpath = "/sakai_screensteps_" . $chapter_id . "/";
    $help_dirs[] = str_replace ("/", "", $destpath);

    $chap = $xmlcontents->addChild('bean');
    $chap->addAttribute('id', 'org.sakaiproject.api.app.help.TableOfContents');
    $chap->addAttribute('class', 'org.sakaiproject.component.app.help.model.TableOfContentsBean');

    $chap_name = $chap->addChild('property');
    $chap_name->addAttribute('name', 'name');
    $chap_name->addChild('value', 'root');

    $chap_categories = $chap->addChild('property');
    $chap_categories->addAttribute('name', 'categories');

    $chap_list = $chap_categories->addChild('list');

    $chap_bean_cat = $chap_list->addChild('bean');
    $chap_bean_cat->addAttribute('id', $chapter_id);
    $chap_bean_cat->addAttribute('class', 'org.sakaiproject.component.app.help.model.CategoryBean');

    $chap_bean_name = $chap_bean_cat->addChild('property');
    $chap_bean_name->addAttribute('name', 'name');
    $chap_bean_name->addChild('value', escape_for_xml ($chapter_title));

    $chap_bean_resources = $chap_bean_cat->addChild('property');
    $chap_bean_resources->addAttribute('name', 'resources');

    $chap_bean_list = $chap_bean_resources->addChild('list');
  }

  $default_for_chapter = true;
  foreach ($chapter->branch()->find('ul li div a') AS $article) {
    $article_text = escape_for_xml ($article->text());
    $article_href = $article->attr('href');
    $href_parts = explode("/", $article_href);
    $article_file = array_pop ($href_parts);
    $article_id = escape_for_id ($article_file);

    $bean = $xmlcontents->addChild('bean');
    $bean->addAttribute('id', $article_id);
    $bean->addAttribute('class', 'org.sakaiproject.component.app.help.model.ResourceBean');

    $docId = $bean->addChild('property');
    $docId->addAttribute('name', 'docId');
    $docId->addChild('value', $article_id);

    $name = $bean->addChild('property');
    $name->addAttribute('name', 'name');
    $name->addChild('value', $article_text);

    $location = $bean->addChild('property');
    $location->addAttribute('name', 'location');
    $location->addChild('value', $destpath . $article_file);

    if ($default_for_chapter) {
      $default_property = $bean->addChild('property');
      $default_property->addAttribute('name', 'defaultForTool');
      $default_property->addChild('value', get_default_tool($chapter_id));
      $default_for_chapter = false;
    }

    $chap_bean_ref = $chap_bean_list->addChild('ref');
    $chap_bean_ref->addAttribute('bean', $article_id);

    // Manipulate the HTML file
    $html_string = file_get_contents_utf8 ($basepath . $article_href);
    $html_title = htmlqp ($html_string, 'title', $qp_options);
    $help_qp = htmlqp ($html_string, 'div#wrapper', $qp_options);

    // Loop through all images and point to /library/ location
    foreach ($help_qp->find('img') AS $html_img) {
      $old_image = $html_img->attr('src');
      $new_image = str_replace ("../images/", "/library/image/help/", $old_image);
      $html_img->attr('src', $new_image);
    }

    // Loop through all links and re-point them
    foreach ($help_qp->branch()->find('a') AS $link) {
      $old_link = $link->attr('href');
      $new_link = "content.hlp?docId=" . escape_for_id ($old_link);
      $link->attr('href', $new_link);
    }

    // Build the new HTML file
    $new_html = 
      qp(QueryPath::XHTML_STUB) // create a clean XHTML file
      ->find('title')->text($html_title->text()) // set the title
      ->top()->find('head')->append($sakai_css)  // add the sakai css
      ->top()->find('body')->append($help_qp)    // add our modified HTML chunk
      ->top()->remove('div#article-header p');   // remove the Table of Contents link

    $ret = $new_html->writeXHTML($svnpath . $destpath . $article_file);
    if (!$ret) print "ERROR: problem copying " . $basepath . $article_href . " to " . $svnpath . $destpath . "\n";
  }
 
  // write the xml to file
  if (!is_dir ($svnpath . $destpath)) {
    if (mkdir ($svnpath . $destpath)) {
      print "INFO: made new dir " . $svnpath . $destpath . "\n";
    } 
    else {
      print "ERROR: new dir " . $svnpath . $destpath . "\n";
    }
  }

  file_put_contents ($svnpath . $destpath . "help.xml", pretty_print_xml ($xmlcontents));
}

// Write all of our possible directories to the sakai.help.xml file
$dirs = implode (",", $help_dirs);
$helpxml = simplexml_load_string($helpxml_file);
$help_config = $helpxml->tool->addChild('configuration');
$help_config->addAttribute('name', 'help.collections');
$help_config->addAttribute('value', $dirs);
$help_config->addAttribute('type', 'final');

file_put_contents ($helpxml_file_svn, pretty_print_xml ($helpxml));
