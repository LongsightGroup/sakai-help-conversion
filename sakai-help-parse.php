<?php
require 'vendor/autoload.php';
require 'helperfunctions.php';
require "lib/ScreenSteps.php";
define('SITE_ID', 5276);
use lib\ScreenSteps;

$basepath = "/tmp/help/";
$svnpath = "/Users/samo/dev/trunk-git/help/help/src";
$toc_master_dir = $svnpath . "/sakai_toc/";
if (!is_dir($toc_master_dir)) mkdir($toc_master_dir);

$helpxml_file = file_get_contents ("sakai.help.xml");
$helpxml_file_svn = $svnpath . "/../../help-tool/src/webapp/WEB-INF/tools/sakai.help.xml";

$api = new ScreenSteps('sakaiexport', 'NmH7P198y6', 'sakai');
$manuals = $api->showSite(SITE_ID);

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

$help_dirs = array('sakai_toc');
$articles_processed = array();

foreach ($manuals->site->manuals AS $manual) {
  if ($manual->id !== 93064 && $manual->id !== 93065) continue;
  $guide_name = $manual->title;
  $guide_name = str_replace(' (English)', '', $guide_name);
  $guide_name = str_replace('Sakai 12', '', $guide_name);
  $guide_name = str_replace('Sakai 19', '', $guide_name);
  $guide_name = str_replace('Sakai 20', '', $guide_name);
  $guide_name = trim($guide_name);
  $chapters = $api->showManual(SITE_ID, $manual->id);

  $toc_ref = $toc_list->addChild('ref');
  $toc_ref->addAttribute('bean', escape_for_id ($guide_name));

  $guide_bean_cat = $xmlcontents->addChild('bean');
  $guide_bean_cat->addAttribute('id', escape_for_id ($guide_name));
  $guide_bean_cat->addAttribute('class', 'org.sakaiproject.component.app.help.model.CategoryBean');

  $guide_bean_name = $guide_bean_cat->addChild('property');
  $guide_bean_name->addAttribute('name', 'name');
  $guide_bean_name->addChild('value', $guide_name);

  $guide_bean_resources = $guide_bean_cat->addChild('property');
  $guide_bean_resources->addAttribute('name', 'categories');
  $guide_bean_list = $guide_bean_resources->addChild('list');

  foreach ($chapters->manual->chapters AS $id => $chapter) {
    if ($chapter->published === false) continue;
  
    $articles = $api->showChapter(SITE_ID, $chapter->id);
    $chapter_id = escape_for_id ($chapter->title . "-" . $guide_name);

    // Add this chapter to the global TOC
    $guide_sub_cat = $guide_bean_list->addChild('ref');
    $guide_sub_cat->addAttribute('bean', $chapter_id);

    $destpath = "/sakai_screensteps_" . $chapter_id . "/";
    $help_dirs[] = str_replace ("/", "", $destpath);

    // This is the tool category
    $chap_bean_cat = $xmlcontents->addChild('bean');
    $chap_bean_cat->addAttribute('id', $chapter_id);
    $chap_bean_cat->addAttribute('class', 'org.sakaiproject.component.app.help.model.CategoryBean');

    $chap_bean_name = $chap_bean_cat->addChild('property');
    $chap_bean_name->addAttribute('name', 'name');
    $chap_bean_name->addChild('value', escape_for_xml ($chapter->title));

    $chap_bean_resources = $chap_bean_cat->addChild('property');
    $chap_bean_resources->addAttribute('name', 'resources');

    // We will fill in the list of articles below
    $chap_bean_list = $chap_bean_resources->addChild('list');

    $first_item_in_chapter = true;

    foreach ($articles->chapter->articles AS $article) {
      $a = $api->showArticle(SITE_ID, $article->id);
      $article_text = $a->article->html_body;
      $article_id = escape_for_id ($a->article->title);
      $article_file = $article_id . '.html';

      if (empty($article_id)) {
        print "Found empty id: " . print_r($a->article) . "\n";
        continue;
      }

      // See if the file already exists in a different case (macOS is case insensitive)
      if ($existing_files = @scandir($svnpath . $destpath)) {
        foreach ($existing_files AS $ef) {
          if (strlen($ef) < 5) continue;

          if (strtolower($ef) === strtolower($article_id . '.html') && $ef !== ($article_id . '.html')) {
            $article_file = $ef;
            $article_id = str_replace('.html', '', $ef);
          }
        }
      }

      $chap_bean_ref = $chap_bean_list->addChild('ref');
      $chap_bean_ref->addAttribute('bean', $article_id);

      if (stripos( $a->article->title, "What are some guidelines") !== FALSE ) {
      //var_dump($a);die();
      }
      else {
        //print $a->article->title ."\n";
      }

      // An article with the same ID is identical between the student and instructor guide
      if (in_array ($article_id, $articles_processed)) {
        continue;
      }
      else {
        $articles_processed[] = $article_id;
      }

      $bean = $xmlcontents->addChild('bean');
      $bean->addAttribute('id', $article_id);
      $bean->addAttribute('class', 'org.sakaiproject.component.app.help.model.ResourceBean');

      $docId = $bean->addChild('property');
      $docId->addAttribute('name', 'docId');
      $docId->addChild('value', $article_id);

      $name = $bean->addChild('property');
      $name->addAttribute('name', 'name');
      $name->addChild('value', escape_for_xml($a->article->title));

      $location = $bean->addChild('property');
      $location->addAttribute('name', 'location');
      $location->addChild('value', $destpath . $article_file);

      $default_for_chapter = get_default_tool(escape_for_id($chapter->title), $article_id, $first_item_in_chapter);
      if (!empty($default_for_chapter)) {
        $default_property = $bean->addChild('property');
        $default_property->addAttribute('name', 'defaultForTool');
        $default_property->addChild('value', $default_for_chapter);
      }

      if (!is_dir ($svnpath . $destpath)) {
        if (mkdir ($svnpath . $destpath)) {
          print "INFO: made new dir " . $svnpath . $destpath . "\n";
        } 
        else {
          print "ERROR: new dir " . $svnpath . $destpath . "\n";
        }
      }

      $article_header = '<div id="article-header"><h1 class="article-title">' . $a->article->title . '</h1></div>';
      $article_html = Htmlawed::filter('<div id="wrapper"><div id="article-content">' . $article_header . 
        '<div id="article-description">' . $article_text . '</div></div></div>', ['unique_ids' => 1] );
      $article_html = str_replace('{{ARTICLE-TEXT}}', $article_html, file_get_contents('sakai-help-stub.html'));
      $article_html = str_replace('{{ARTICLE-DESCRIPTION}}', $default_for_chapter, $article_html);
      $article_html = str_replace('{{ARTICLE-TITLE}}', $a->article->title, $article_html);
      $article_html = clean_html ($article_html);
      $ret = file_put_contents($svnpath . $destpath . $article_file, $article_html);
      if (!$ret) print "ERROR: problem copying $article_id to $svnpath$destpath$article_file \n";

      $first_item_in_chapter = false;
    }
  }
}

file_put_contents ($toc_master_dir . "help.xml", pretty_print_xml ($xmlcontents));

// Write all of our possible directories to the sakai.help.xml file
$dirs = implode (",", array_unique ($help_dirs));
$helpxml = simplexml_load_string($helpxml_file);
$help_config = $helpxml->tool->addChild('configuration');
$help_config->addAttribute('name', 'help.collections');
$help_config->addAttribute('value', $dirs);
$help_config->addAttribute('type', 'final');

file_put_contents ($helpxml_file_svn, pretty_print_xml ($helpxml));
