<?php
require 'vendor/autoload.php';
require 'helperfunctions.php';

$basepath = "/tmp/help/";
$svnpath = "/home/samo/dev/trunk-all/help/help/src";
$toc_master_dir = $svnpath . "/sakai_toc/";
if (!is_dir($toc_master_dir)) mkdir($toc_master_dir);

$helpxml_file = file_get_contents ("sakai.help.xml");
$helpxml_file_svn = $svnpath . "/../../help-tool/src/webapp/tools/sakai.help.xml";

$instructor_file = "Sakai-10-Instructor-Guide.html";
$guide_name = "Instructor Guide";
$student_file = "Sakai-10-Student-Guide.html";

// Default QueryPath options use ISO-8859-1
$qp_options = array(
  'convert_from_encoding' => 'UTF-8',
  'convert_to_encoding' => 'UTF-8',
  'strip_low_ascii' => FALSE,
);

// Create the beginning of the TOC 
$xmlstub = file_get_contents ('sakai-help-contents-stub.xml');
$xmlcontents = simplexml_load_string($xmlstub);

$toc = $xmlcontents->addChild('bean');
$toc->addAttribute('id', 'org.sakaiproject.api.app.help.TableOfContents');
$toc->addAttribute('class', 'org.sakaiproject.component.app.help.model.TableOfContentsBean');
$toc_name = $toc->addChild('property');
$toc_name->addAttribute('name', 'name');
$toc_name->addChild('value', 'root');
$toc_categories = $toc->addChild('property');
$toc_categories->addAttribute('name', 'categories');
$toc_list = $toc_categories->addChild('list');
$toc_ref = $toc_list->addChild('ref');
$toc_ref->addAttribute('bean', escape_for_id ($guide_name));
    
// This is the Instructor parent-item in the TOC
$guide_bean_cat = $xmlcontents->addChild('bean');
$guide_bean_cat->addAttribute('id', escape_for_id ($guide_name));
$guide_bean_cat->addAttribute('class', 'org.sakaiproject.component.app.help.model.CategoryBean');

$guide_bean_name = $guide_bean_cat->addChild('property');
$guide_bean_name->addAttribute('name', 'name');
$guide_bean_name->addChild('value', $guide_name);

$guide_bean_resources = $guide_bean_cat->addChild('property');
$guide_bean_resources->addAttribute('name', 'categories');
$guide_bean_list = $guide_bean_resources->addChild('list');

// Read the Screensteps HTML TOC
$instructor_xml = simplexml_load_string (file_get_contents ($basepath . $instructor_file));
$qp = qp ($instructor_xml, 'div#TOC');

$help_dirs = array('sakai_toc');
foreach ($qp->children('div.chapter-container') AS $chapter) {

  foreach ($chapter->branch()->children('h2') AS $chapter_h2) {
    $chapter_title = $chapter_h2->text();
    $chapter_id = escape_for_id ($chapter_title);
    $destpath = "/sakai_screensteps_" . $chapter_id . "/";
    $help_dirs[] = str_replace ("/", "", $destpath);

    // This is the tool category
    $chap_bean_cat = $xmlcontents->addChild('bean');
    $chap_bean_cat->addAttribute('id', $chapter_id);
    $chap_bean_cat->addAttribute('class', 'org.sakaiproject.component.app.help.model.CategoryBean');

    $chap_bean_name = $chap_bean_cat->addChild('property');
    $chap_bean_name->addAttribute('name', 'name');
    $chap_bean_name->addChild('value', escape_for_xml ($chapter_title));

    $chap_bean_resources = $chap_bean_cat->addChild('property');
    $chap_bean_resources->addAttribute('name', 'resources');

    // We will fill in the list of articles below
    $chap_bean_list = $chap_bean_resources->addChild('list');

    // Add this chapter to the global TOC
    $guide_sub_cat = $guide_bean_list->addChild('ref');
    $guide_sub_cat->addAttribute('bean', $chapter_id);
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
      $parsed_link = parse_url ($old_link);
      if (!empty ($parsed_link['scheme'])) continue;

      $new_link = "content.hlp?docId=" . escape_for_id ($old_link);
      $link->attr('href', $new_link);
      $link->removeAttr('target');
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

    $ret = $help_qp->writeXHTML($svnpath . $destpath . $article_file);
    if (!$ret) print "ERROR: problem copying " . $basepath . $article_href . " to " . $svnpath . $destpath . "\n";
  }

}

file_put_contents ($toc_master_dir . "help.xml", pretty_print_xml ($xmlcontents));

// Write all of our possible directories to the sakai.help.xml file
$dirs = implode (",", $help_dirs);
$helpxml = simplexml_load_string($helpxml_file);
$help_config = $helpxml->tool->addChild('configuration');
$help_config->addAttribute('name', 'help.collections');
$help_config->addAttribute('value', $dirs);
$help_config->addAttribute('type', 'final');

file_put_contents ($helpxml_file_svn, pretty_print_xml ($helpxml));
