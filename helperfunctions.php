<?php

function escape_for_id ($string) {
  $string = str_replace (".html", "", $string);
  $string = preg_replace ('/[^A-Za-z0-9]/', "", $string);
  return lcfirst ($string);
}

function escape_for_xml ($string) {
  return htmlspecialchars (html_entity_decode ($string, ENT_XHTML));
}

function pretty_print_xml ($xml) {
  $dom = new DOMDocument('1.0');
  $dom->preserveWhiteSpace = false;
  $dom->formatOutput = true;
  $dom->loadXML($xml->asXML());
  return $dom->saveXML();
}

function file_get_contents_utf8 ($filename) { 
  $string = file_get_contents ($filename); 
  return mb_convert_encoding($string, 'UTF-8', mb_detect_encoding ($string, 'UTF-8, ISO-8859-1', true)); 
} 

function clean_html($html_string) {
  $html_string = preg_replace("/\.png\?([^\"])*\"/", ".png\"", $html_string);

  // Default QueryPath options use ISO-8859-1
  $qp_options = array(
    'convert_from_encoding' => 'UTF-8',
    'convert_to_encoding' => 'UTF-8',
    'strip_low_ascii' => FALSE,
  );

  $help_qp = htmlqp ($html_string, NULL, $qp_options);

  // Loop through all images and point to /library/ location
  /*
  foreach ($help_qp->find('img') AS $html_img) {
    $old_image = $html_img->attr('src');
    $new_image = str_replace ("../images/", "/library/image/help/en/", $old_image);
    $html_img->attr('src', $new_image);
  }
  */

  // Loop through all links and re-point them
  foreach ($help_qp->branch()->find('a') AS $link) {
    $old_link = $link->attr('href');
    $link_rel = $link->attr('rel');
    $parsed_link = parse_url ($old_link);

    if (!empty ($parsed_link['scheme'])) {
      continue;
    }
    elseif (!empty($link_rel) && $link_rel == 'prettyPhoto') {
      $new_link = str_replace ("../images/", "/library/image/help/en/", $old_link);
      $link->attr('href', $new_link);

      // Replace jQuery prettyPhoto with featherlight
      $link->attr('rel', 'featherlight');
    }
    else if (strpos($old_link, '../../68426/l/') !== FALSE) {
      $tmp = str_replace('../../68426/l/', '', $old_link);
      $pieces = explode('-', $tmp);
      array_shift($pieces);
      $mod_link = implode(' ', $pieces);
      $new_link = "content.hlp?docId=" . escape_for_id ($mod_link);
      $link->attr('href', $new_link);
      $link->removeAttr('target');
    }
  }

  return $help_qp->html();
}

function get_default_tool ($tool, $article_id, $first_article_in_chapter) {
  $tool = str_replace ("OSP", "", $tool);

  switch ($article_id) {
   case 'whatistheHomeCalendar':
     return 'sakai.summary.calendar';
   case 'whataretheHomeMessageCenterNotifications':
     return 'sakai.synoptic.messagecenter';
   case 'whatisHome':
     return 'sakai.iframe.myworkspace';
   case 'whatistheHomeMessageoftheDay':
     return 'sakai.motd';
   case 'whataretheHomeRecentAnnouncements':
     return 'sakai.synoptic.announcements';
    case 'whatisthePreferencestool': 
      return 'sakai.preferences';
    case 'howdoIviewandeditmyaccountdetails': 
      return 'sakai.singleuser';

    // Special case because was going to admin help
    case 'whatistheResourcestool': 
      return 'sakai.resources';
    case 'whatistheSearchtool': 
      return 'sakai.search';
  }

  // Special cases are above
  if (!$first_article_in_chapter) {
    return false;
  }

  switch ($tool) {
    case 'accessibility': 
      return 'sakai.accessibility';
    case 'myWorkspace': 
      return 'sakai.iframe.myworkspace';
    case 'announcements': 
      return 'sakai.announcements';
    case 'assignments': 
      return 'sakai.assignment';
    case 'chat': 
      return 'sakai.chat';
    case 'contactUs': 
      return 'sakai.feedback';
    case 'dropBox': 
      return 'sakai.dropbox';
    case 'email': 
      return 'sakai.mailsender';
    case 'emailArchive': 
      return 'sakai.mailbox';
    case 'externalToolLTI': 
      return 'sakai.basiclti';
    case 'forms': 
      return 'sakai.metaobj';
    case 'forums': 
      return 'sakai.forums';
    case 'gradebookclassic': 
      return 'sakai.gradebook.tool';
    case 'gradebook': 
      return 'sakai.gradebookng';
    case 'jobScheduler': 
      return 'sakai.scheduler';
    case 'lessons': 
      return 'sakai.lessonbuildertool';
    case 'messages': 
      return 'sakai.messages';
    case 'news': 
      return 'sakai.simple.rss';
    case 'podcasts': 
      return 'sakai.podcasts';
    case 'polls': 
      return 'sakai.poll';
    case 'postEm': 
      return 'sakai.postem';
    case 'profile': 
      return 'sakai.profile2';
    case 'roster': 
      return 'sakai.site.roster2';
    case 'calendar': 
      return 'sakai.schedule';
    case 'sectionInfo': 
      return 'sakai.sections';
    case 'signUp': 
      return 'sakai.signup';
    case 'siteArchive': 
      return 'sakai.archive';
    case 'siteInfo': 
      return 'sakai.siteinfo';
    case 'sitestatsAdmin': 
      return 'sakai.sitestats.admin';
    case 'statistics': 
      return 'sakai.sitestats';
    case 'syllabus': 
      return 'sakai.syllabus';
    case 'testsandQuizzes': 
      return 'sakai.samigo';
    case 'userMembership': 
      return 'sakai.membership';
    case 'webContent': 
      return 'sakai.iframe';
    case 'wiki': 
      return 'sakai.rwiki';
    case 'worksiteSetup': 
      return 'sakai.sitesetup';

    // Dont allow admin tool defaults
    case 'resources': 
    case 'search': 
      return null;

    default:
     return "sakai.$tool";
  }
}

