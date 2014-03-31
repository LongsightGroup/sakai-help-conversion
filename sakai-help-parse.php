<?php
require 'vendor/autoload.php';
require 'helperfunctions.php';

$xmlstub = file_get_contents ('sakai-help-contents-stub.xml');
$xmlcontents = simplexml_load_string($xmlstub);

$basepath = "/tmp/help/";
$destpath = "/sakai_screensteps/";
$instructor_file = "Sakai-10-Instructor-Guide.html";
$student_file = "Sakai-10-Student-Guide.html";

$instructor_xml = simplexml_load_string (file_get_contents ($basepath . $instructor_file));

$qp = qp ($instructor_xml, 'div#TOC');

foreach ($qp->children('div.chapter-container') AS $chapter) {
  foreach ($chapter->branch()->children('h2') AS $chapter_h2) {
    $chapter_title = $chapter_h2->text();
    $chapter_id = escape_for_id ($chapter_title);

    $chap = $xmlcontents->addChild('bean');
    $chap->addAttribute('id', 'org.sakaiproject.api.app.help.TableOfContents.' . $chapter_id);
    $chap->addAttribute('class', 'org.sakaiproject.component.app.help.model.TableOfContentsBean');

    $chap_name = $chap->addChild('property', 'root');
    $chap_name->addAttribute('name', 'name');

    $chap_categories = $chap->addChild('property');
    $chap_categories->addAttribute('name', 'categories');

    $chap_list = $chap_categories->addChild('list');

    $chap_bean_cat = $chap_list->addChild('bean');
    $chap_bean_cat->addAttribute('id', $chapter_id);
    $chap_bean_cat->addAttribute('class', 'org.sakaiproject.component.app.help.model.CategoryBean');

    $chap_bean_name = $chap_bean_cat->addChild('property', escape_for_xml ($chapter_title));
    $chap_bean_name->addAttribute('name', 'name');

    $chap_bean_resources = $chap_bean_cat->addChild('resources');
    $chap_bean_resources->addAttribute('name', 'resources');

    $chap_bean_list = $chap_bean_resources->addChild('list');
  }

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

    $location = $bean->addChild('property', $destpath . $article_file);
    $location->addAttribute('name', 'location');

    if ($default_for_chapter) {
      $default_property = $bean->addChild('property', get_default_tool($chapter_id));
      $default_property->addAttribute('name', 'defaultForTool');
      $default_for_chapter = false;
    }

    $chap_bean_ref = $chap_bean_list->addChild('ref');
    $chap_bean_ref->addAttribute('bean', $article_id);
  }
}

print ($xmlcontents->asXML());
