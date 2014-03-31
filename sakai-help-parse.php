<?php
require 'vendor/autoload.php';
require 'helperfunctions.php';

$xmlstub = file_get_contents ('sakai-help-contents-stub.xml');
$xmlcontents = simplexml_load_string($xmlstub);


$basepath = "/tmp/help/";
$instructor_file = "Sakai-10-Instructor-Guide.html";
$student_file = "Sakai-10-Student-Guide.html";

$instructor_xml = simplexml_load_string (file_get_contents ($basepath . $instructor_file));

$qp = qp ($instructor_xml, 'div#TOC');

foreach ($qp->children('div.chapter-container') AS $chapter) {
  foreach ($chapter->branch()->children('h2') AS $chapter_h2) {
    $chapter_title = $chapter_h2->text();
    $chapter_id = escape_for_id ($chapter_title);

    $chap = $xmlcontents->addChild('bean');
    $chap->addAttribute('id', $chapter_id);
    $chap->addAttribute('class', 'org.sakaiproject.component.app.help.model.ResourceBean');
  }

  //print "    case '$chapter_id': \n      return 'sakai.$chapter_id';\n";continue;

  $default_for_chapter = true;
  foreach ($chapter->branch()->find('ul li div a') AS $article) {
    $article_text = escape_for_xml ($article->text());
    $article_href = $article->attr('href');
    $href_parts = explode("/", $article_href);
    $article_file = array_pop ($href_parts);
    $article_id = str_replace (".html", "", $article_file);
    $article_id = strtolower ($article_id);
    $article_id = str_replace (".", "", $article_id );
    $article_id = str_replace ("--", "-", $article_id );
    $article_id = str_replace ("--", "-", $article_id );
    if (strrpos($article_id, "-") == (strlen($article_id)-1)) $article_id = substr ($article_id, 0,strlen($article_id) -1);

    $bean = $xmlcontents->addChild('bean');
    $bean->addAttribute('id', $article_id);
    $bean->addAttribute('class', 'org.sakaiproject.component.app.help.model.ResourceBean');

    $docId = $bean->addChild('property', $article_id);
    $docId->addAttribute('name', 'docId');

    $name = $bean->addChild('property', $article_text);
    $name->addAttribute('name', 'name');

    $location = $bean->addChild('property', $article_href);
    $location->addAttribute('name', 'location');

    if ($default_for_chapter) {
      $default_property = $bean->addChild('property', get_default_tool($chapter_id));
      $default_property->addAttribute('name', 'defaultForTool');
      $default_for_chapter = false;
    }

  }
}

print ($xmlcontents->asXML());
