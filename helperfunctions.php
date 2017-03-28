<?php

function escape_for_id ($string) {
  $string = str_replace (".html", "", $string);
  $string = preg_replace ('/[^A-Za-z0-9]/', "", $string);
  return lcfirst ($string);
}

function escape_for_xml ($string) {
  return htmlentities ($string);
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
    case 'resources': 
      return 'sakai.resources';
    case 'roster': 
      return 'sakai.site.roster2';
    case 'calendar': 
      return 'sakai.schedule';
    case 'search': 
      return 'sakai.search';
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
      return 'sakai.usermembership';
    case 'webContent': 
      return 'sakai.iframe';
    case 'wiki': 
      return 'sakai.rwiki';
    case 'worksiteSetup': 
      return 'sakai.sitesetup';

    // OSP Tools
    case 'matrices': 
      return 'osp.matrix';
    case 'portfolioTemplates':
      return 'osp.presTemplate';
    case 'portfolios':
      return 'osp.presentation';
    case 'evaluations':
      return 'osp.evaluation';
    case 'glossary':
      return 'osp.glossary';
    case 'styles':
      return 'osp.style';

    default:
     return "sakai.$tool";
  }
}

