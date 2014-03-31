<?php
require 'vendor/autoload.php';

$xmlstub = file_get_contents ('sakai-help-contents-stub.xml');
$xmlcontents = simplexml_load_string($xmlstub);


$basepath = "/tmp/help/";
$instructor_file = "Sakai-10-Instructor-Guide.html";
$student_file = "Sakai-10-Student-Guide.html";

$instructor_xml = simplexml_load_string (file_get_contents ($basepath . $instructor_file));

$qp = qp ($instructor_xml, 'div#TOC');

foreach ($qp->children('div.chapter-container') AS $chapter) {
  $chapter_title = "";

  foreach ($chapter->branch()->children('h2') AS $chapter_h2) {
    $chapter_title = $chapter_h2->text();

    $chap = $xmlcontents->addChild('bean');
    $chap->addAttribute('id', $chapter_title);
  }

  foreach ($chapter->branch()->find('ul li div a') AS $article) {
    $article_text = $article->text();
    $article_href = $article->attr('href');
    $href_parts = explode("/", $article_href);
    $article_file = array_pop ($href_parts);
    $article_id = str_replace (".html", "", $article_file);
    $article_id = strtolower ($article_id);
    $article_id = str_replace (".", "", $article_id );
    $article_id = str_replace ("--", "-", $article_id );
    $article_id = str_replace ("--", "-", $article_id );
    if (strrpos($article_id, "-") == (strlen($article_id)-1)) $article_id = substr ($article_id, 0,strlen($article_id) -1);

    //var_dump($article_id);
    $tt = $chap->addChild('tt');
    $tt->addAttribute('z', 'xx');
  }
}

print ($xmlcontents->asXML());
