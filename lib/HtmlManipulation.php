<?php

namespace lib;

class HtmlManipulation {

    public function cleanHtml($html_string) {
      $help_qp = htmlqp ($html_string, 'div#wrapper', $qp_options);

      // Loop through all images and point to /library/ location
      foreach ($help_qp->find('img') AS $html_img) {
        $old_image = $html_img->attr('src');
        $new_image = str_replace ("../images/", "/library/image/help/en/", $old_image);
        $html_img->attr('src', $new_image);
      }

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
        else {
          $new_link = "content.hlp?docId=" . escape_for_id ($old_link);
          $link->attr('href', $new_link);
          $link->removeAttr('target');
        }
      }


    }
}