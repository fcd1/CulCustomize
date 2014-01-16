<?php

class CulCustomizePlugin extends Omeka_Plugin_AbstractPlugin
{

  protected $_filters = array('admin_items_form_tabs');

  public function filterAdminItemsFormTabs($tabs, $args)
  {

    $local_array = array();
    $local_array['Dublin Core'] = $tabs['Dublin Core'];
    $local_array['MODS'] = $tabs['MODS'];

    foreach ($tabs as $key => $value)
    {
      $local_array[$key] = $value;
    }

    return $local_array;

  }
}

add_filter('exhibit_builder_exhibit_display_item', 
           'filter_exhibit_builder_exhibit_display_item');

function filter_exhibit_builder_exhibit_display_item ($html, $displayFilesOptions, $linkProperties, $item) 
{
  if (item_has_type('Sound', $item)) {
    // link using the icon in your theme's images directory.
    $iconURL = img('200px-Loudspeaker.svg.png');
    $iconHTML = "<img width='100px' src='$iconURL'><br />";
    $html = exhibit_builder_link_to_exhibit_item( $iconHTML ); 
    $html .= exhibit_builder_link_to_exhibit_item();
  }

  return $html;
}

function cul_general_get_unit_link()
{
  $unit = get_theme_option('Library Unit');
  if (! isset($unit) or $unit == "")
    return "";

  $unit_link = "";

  if ($unit == "rbml") 
    $unit_link = '<a href="http://library.columbia.edu/indiv/rbml.html" class="topNavbarLink" style="text-decoration: none;">Rare Book &amp; Manuscript Library</a>';

  if ($unit == "burke") 
    $unit_link = '<a href="http://library.columbia.edu/indiv/burke.html" class="topNavbarLink" style="text-decoration: none;">Burke Theological Library</a>';

  if ($unit == "avery") 
    $unit_link = '<a href="http://library.columbia.edu/indiv/avery.html" class="topNavbarLink" style="text-decoration: none;">Avery Architectural &amp; Fine Arts Library</a>';

  if ($unit == "starr")
    $unit_link = '<a href="http://library.columbia.edu/indiv/eastasian.html" class="topNavbarLink" style="text-decoration: none;">C.V. Starr East Asian Library</a>';

  // added by fcd1, 7/15/13: Add entry for the Digital Humanities Center
  if ($unit == "dhc")
    $unit_link = '<a href="http://library.columbia.edu/locations/dhc.html" class="topNavbarLink" style="text-decoration: none;">Digital Humanities Center</a>';

  return $unit_link;
}

function cul_general_get_unit_contact()
{
  $unit = get_theme_option('Library Unit');
  if (! isset($unit) or $unit == "")
    return "";

  $unit_contact = "";

  if ($unit == "rbml") 
    $unit_contact = 'Rare Book &amp; Manuscript Library / Butler Library, 6th Fl. East / 535 West 114th St. / New York, NY 10027 / (212) 854-5153 / <a href="mailto:rbml@libraries.cul.columbia.edu">rbml@libraries.cul.columia.edu</a>';

  if ($unit == "burke")
    $unit_contact = 'The Burke Library (Columbia University Libraries) / 3041 Broadway at 121st Street / New York, NY 10027 / (212) 851-5606 / <a href="mailto:burke@libraries.cul.columbia.edu">burke@libraries.cul.columbia.edu</a>';

  if ($unit == "avery")
    $unit_contact = 'Avery Architectural &amp; Fine Arts Library / 300 Avery, M.C. 0301 / 1172 Amsterdam Avenue / New York, NY 10027 / (212) 854-3501 / <a href="mailto:avery@libraries.cul.columbia.edu">avery@libraries.cul.columbia.edu</a>';

  if ($unit == "starr")
    $unit_contact = 'C.V. Starr East Asian Library / 300 Kent Hall,  M.C. 3901 / 1140 Amsterdam Avenue / New York, NY 10027 / (212) 854-4318 / <a href="mailto:starr@libraries.cul.columbia.edu">starr@libraries.cul.columbia.edu</a>';

  // added by fcd1, 7/15/13: Add entry for the Digital Humanities Center
  if ($unit == "dhc")
    $unit_contact = 'Digital Humanities Center  / 305 Butler Library  / 535 West 114th St. / New York, NY 10027 / (212) 854-7547 / <a href="mailto:dhc@libraries.cul.columbia.edu">dhc@libraries.cul.columbia.edu</a>';

  if ($unit == "ldpd")
    $unit_contact = 'Libraries Digital Program Division  / Butler Library  / 535 West 114th St. / New York, NY 10027 / <a href="mailto:ldpd@libraries.cul.columbia.edu">ldpd@libraries.cul.columbia.edu</a>';

  return $unit_contact;
}

function cul_general_legacy_exhibit_builder_page_nav($home_link_title = null,
						     $exhibitPage = null)
{
  if (!$exhibitPage) {
    if (!($exhibitPage = get_current_record('exhibit_page', false))) {
      return "get_current_record returned null";
    }
  }
  $exhibit = $exhibitPage->getExhibit();
  $pagesTrail = $exhibitPage->getAncestors();
  $pagesTrail[] = $exhibitPage;
  $html = '<ul class="exhibit-page-nav navigation" id="secondary-nav">' . "\n";
  $html .= '<li id="cul-general-exhibit-nav-title">';
  $html .= '<a class="exhibit-title" href="'. 
           html_escape(exhibit_builder_exhibit_uri($exhibit)) . '">';
  if ($home_link_title) {
    $html .= $home_link_title .'</a></li>' . "\n";
  } else {
    $html .= cul_insert_angle_brackets(html_escape($exhibit->title)) .
             '</a></li>' . "\n";
  }
  $page = array_shift($pagesTrail);
  $linkText = $page->title;
  $pageExhibit = $page->getExhibit();
  $pageParent = $page->getParent();
  $pageSiblings = ($pageParent ? exhibit_builder_child_pages($pageParent) : 
                                 $pageExhibit->getTopPages()); 
  $html .= '<li class="precedes-ul-tag">' . 
           '<ul class="exhibit-page-nav navigation">' . "\n";
  foreach ($pageSiblings as $pageSibling) {
    $html .= '<li' . ($pageSibling->id == $page->id ? ' class="current"' : '') . '>';
    $html .= '<a href="' . 
             html_escape(exhibit_builder_exhibit_uri($exhibit, $pageSibling)) . '">';
    $html .= cul_insert_angle_brackets(html_escape($pageSibling->title)) .
	      "</a></li>\n";
    if ($pageSibling->id == $page->id) {
      if ($pagesTrail) {
        $html .= cul_exhibit_builder_child_page_nav(array_shift($pagesTrail),
						     $pageSibling);
      }
      else {
        $html .= exhibit_builder_child_page_nav();
      }
    }
  }
  $html .= "</ul>\n</li>\n";
  $html .= '</ul>' . "\n";
  $html = apply_filters('exhibit_builder_page_nav', $html);
  return $html;
}

function cul_general_exhibit_builder_page_nav($home_link_title = null,
						     $exhibitPage = null)
{
  if (!$exhibitPage) {
    if (!($exhibitPage = get_current_record('exhibit_page', false))) {
      return "get_current_record returned null";
    }
  }
  $exhibit = $exhibitPage->getExhibit();
  $pagesTrail = $exhibitPage->getAncestors();
  $pagesTrail[] = $exhibitPage;
  $html = '<ul class="exhibit-page-nav navigation" id="secondary-nav">' . "\n";
  $html .= '<li id="cul-general-exhibit-nav-title">';
  $html .= '<a class="exhibit-title" href="'. 
           html_escape(exhibit_builder_exhibit_uri($exhibit)) . '">';
  if ($home_link_title) {
    $html .= $home_link_title .'</a></li>' . "\n";
  } else {
    $html .= cul_insert_angle_brackets(html_escape($exhibit->title)) .
             '</a></li>' . "\n";
  }
  $page = array_shift($pagesTrail);
  $linkText = $page->title;
  $pageExhibit = $page->getExhibit();
  $pageParent = $page->getParent();
  $pageSiblings = ($pageParent ? exhibit_builder_child_pages($pageParent) : 
                                 $pageExhibit->getTopPages()); 
  $html .= '<li class="precedes-ul-tag">' . 
           '<ul class="exhibit-page-nav navigation">' . "\n";
  foreach ($pageSiblings as $pageSibling) {
    $html .= '<li' . ($pageSibling->id == $page->id ? ' class="current"' : '') . '>';
    $html .= '<a href="' . 
             html_escape(exhibit_builder_exhibit_uri($exhibit, $pageSibling)) . '">';
    $html .= cul_insert_angle_brackets(html_escape($pageSibling->title)) .
	      "</a></li>\n";
    if ($pageSibling->id == $page->id) {
      if ($pagesTrail) {
        $html .= cul_exhibit_builder_child_page_nav(array_shift($pagesTrail),
						     $pageSibling);
      }
      else {
        $html .= exhibit_builder_child_page_nav();
      }
    }
  }
  $html .= "</ul>\n</li>\n";
  $html .= '</ul>' . "\n";
  $html = apply_filters('exhibit_builder_page_nav', $html);
  return $html;
}

function cul_exhibit_builder_child_page_nav($page,$exhibitPage = null)
{
  if (!$exhibitPage) {
    $exhibitPage = get_current_record('exhibit_page');
  }
  $exhibit = $exhibitPage->getExhibit();
  $children = exhibit_builder_child_pages($exhibitPage);
  $html = '<li class="precedes-ul-tag"><ul class="exhibit-child-nav navigation">' .
          "\n";
  foreach ($children as $child) {
    if ($child->id == $page->id) {
      $html .= '<li class="current"><a href="' . 
               html_escape(exhibit_builder_exhibit_uri($exhibit, $child)) . '">' . 
               cul_insert_angle_brackets(html_escape($child->title)) . '</a></li>';
      $html .= cul_exhibit_builder_grandchild_page_nav($child);
    }
    else {
      $html .= '<li><a href="' . 
      html_escape(exhibit_builder_exhibit_uri($exhibit, $child)) . '">' . 
	          cul_insert_angle_brackets(html_escape($child->title)) .
                  '</a></li>';
    }
  }
  $html .= '</ul></li>' . "\n";
  return $html;
}

// fcd1, 8/12/13: following method is based on exhibit_builder_child_page_nav()
// found in plugins/ExhibitBuilder/helpers/ExhibitPageFunctions.php,
// with extra code to set class=current if appropriate.
function cul_exhibit_builder_grandchild_page_nav($page,$exhibitPage = null)
{
  if (!$exhibitPage) {
    $exhibitPage = get_current_record('exhibit_page');
  }
  $exhibit = $exhibitPage->getExhibit();
  $children = exhibit_builder_child_pages($page);
  if (!($children)) {
    return;
  }
  $html = '<li class="precedes-ul-tag"><ul class="exhibit-child-nav navigation">' .
          "\n";
  foreach ($children as $child) {
    if ($child->id == $exhibitPage->id) {
      $html .= '<li class="current"><a href="' . 
               html_escape(exhibit_builder_exhibit_uri($exhibit, $child)) . '">' . 
               cul_insert_angle_brackets(html_escape($child->title)) . '</a></li>';
    }
    else {
      $html .= '<li><a href="' . 
               html_escape(exhibit_builder_exhibit_uri($exhibit, $child)) . '">' . 
               cul_insert_angle_brackets(html_escape($child->title)) . '</a></li>';
    }
  }
  $html .= '</ul></li>' . "\n";
  return $html;
}

/**
 * fcd1, 8/12/13: following method is based on exhibit_builder_child_page_nav() found in 
 * plugins/ExhibitBuilder/helpers/ExhibitPageFunctions.php, with extra code to set
 * class=current if appropriate
 */

function fcd1_exhibit_builder_grandchild_page_nav($page,$exhibitPage = null)
{
    if (!$exhibitPage) {
        $exhibitPage = get_current_record('exhibit_page');
    }

    $exhibit = $exhibitPage->getExhibit();
    // $children = exhibit_builder_child_pages($exhibitPage);
    $children = exhibit_builder_child_pages($page);
    // fcd1, 8/12/13: added this. No need to create a list if there are no children, so just return
    if (!($children)) {
      return;
    }
    $html = '<li class="precedes-ul-tag"><ul class="exhibit-child-nav navigation pickles">' . "\n";
    foreach ($children as $child) {
      if ($child->id == $exhibitPage->id) {
        $html .= '<li class="current"><a href="' . 
	  html_escape(exhibit_builder_exhibit_uri($exhibit, $child)) . '">' . 
	  cul_insert_angle_brackets(html_escape($child->title)) . '</a></li>';
      }
      else {
        $html .= '<li><a href="' . 
	  html_escape(exhibit_builder_exhibit_uri($exhibit, $child)) . '">' . 
	  cul_insert_angle_brackets(html_escape($child->title)) . '</a></li>';
      }
    }
    $html .= '</ul></li>' . "\n";
    return $html;
}

function fcd1_exhibit_builder_child_page_nav($page,$exhibitPage = null)
{
  if (!$exhibitPage) {
    $exhibitPage = get_current_record('exhibit_page');
  }
  $exhibit = $exhibitPage->getExhibit();
  $children = exhibit_builder_child_pages($exhibitPage);
  $html = '<li class="precedes-ul-tag"><ul class="exhibit-child-nav navigation">' .
          "\n";
  foreach ($children as $child) {
    if ($child->id == $page->id) {
      $html .= '<li class="current"><a href="' . 
               html_escape(exhibit_builder_exhibit_uri($exhibit, $child)) . '">' . 
               cul_insert_angle_brackets(html_escape($child->title)) . '</a></li>';
      $html .= fcd1_exhibit_builder_grandchild_page_nav($child);
    }
    else {
      $html .= '<li><a href="' . 
      html_escape(exhibit_builder_exhibit_uri($exhibit, $child)) . '">' . 
	          cul_insert_angle_brackets(html_escape($child->title)) .
                  '</a></li>';
    }
  }
  $html .= '</ul></li>' . "\n";
  return $html;
}

function cul_general_breadcrumb($currentExhibitPage = null) {
  if (!$currentExhibitPage) {
    $currentExhibitPage = get_current_record('exhibit_page');
  }
  $parentOfExhibitPage = $currentExhibitPage->getParent();
  if ($parentOfExhibitPage) {
    $grandparentOfExhibitPage = $parentOfExhibitPage->getParent();
    if ($grandparentOfExhibitPage) {
      $html = '<h3><a href="' .  exhibit_builder_exhibit_uri('',$grandparentOfExhibitPage) .
	'">' . $grandparentOfExhibitPage->title . "</a> &gt; " .
	'<a href="' .  exhibit_builder_exhibit_uri('',$parentOfExhibitPage) .
	'">' . $parentOfExhibitPage->title . "</a> &gt; " .
	$currentExhibitPage->title . "</h3>\n";
    }  else {
      $html = '<h3><a href="' .  exhibit_builder_exhibit_uri('',$parentOfExhibitPage) .
	'">' . $parentOfExhibitPage->title . "</a> &gt; " .
	$currentExhibitPage->title . "</h3>\n";
    }
  } else {
    $html = '<h3>' . $currentExhibitPage->title . "</h3>\n";
  }

  return $html;
}

// fcd1, 8/17/13: I need to customized this fuction so I can render a given page, not the current page.
// This is used to mimic the behavior for legacy exhibtions of omeka 1.5.3., where sections (i.e. top-level 
// pages) had no landing page and just showed the content of the first child page
/**
 * Display an exhibit page
 *
 * @param ExhibitPage $exhibitPage If null, will use the current exhibit page.
 */
function cul_legacy_exhibit_builder_render_exhibit_page($exhibitPage = null)
{
    if (!$exhibitPage) {
        $exhibitPage = get_current_record('exhibit_page');
    }
    if ($exhibitPage->layout) {
      /*
      include str_replace('exhibit_layouts',
			  'legacy_exhibit_layouts',
			  EXHIBIT_LAYOUTS_DIR) .
      */
      include EXHIBIT_LAYOUTS_DIR .
	'/' . $exhibitPage->layout . '/layout.php';
    } else {
        echo "This page does not have a layout.";
    }
}

/**
 * Return HTML for displaying an attached item on an exhibit page.
 *
 * @see exhibit_builder_page_attachment for attachment array contents
 * @param array $attachment The attachment.
 * @param array $fileOptions Options for file_markup when displaying a file
 * @param array $linkProperties Attributes for use when linking to an item
 * @return string
 */
// fcd1, 8/28/13: based on exhibit_builder_attachment_markup(...), customize it so the images
// link to the larger image, not the item page
function cul_exhibit_builder_attachment_markup($attachment, $fileOptions, $linkProperties)
{
  // fcd1, 8/30/13: creating a link to the item page should always occur, it cannot be an option
  // If it needs to be an option, uncomment the get_theme_option call below
  //  $link_to_item = get_theme_option('Link Item Page');
  $link_to_item = 1;
  
  if (!$attachment) {
    return '';
  }

  $item = $attachment['item'];
  $file = $attachment['file'];
  $mime_type = metadata($attachment['file'],'mime_type');

  if (!isset($fileOptions['linkAttributes']['href'])) {
    // fcd1, 8/28/13: commented out original line of code and customize it
    if (($mime_type == 'image/jpeg') || ($mime_type == 'image/gif')) {
      $fileOptions['linkAttributes']['href'] = $file->getWebPath('original');
      // fcd1, 9/24/13: added following two lines for highslide
      $fileOptions['linkAttributes']['onclick'] = 'return hs.expand(this)';
      $fileOptions['linkAttributes']['class'] = 'highslide';
    } else {
      $fileOptions['linkAttributes']['href'] = exhibit_builder_exhibit_item_uri($item);
    }
  }

  // fcd1, 8/28/13: Also added this so full size image (see changes above) opens in
  // new tab
  if (!isset($fileOptions['linkAttributes']['target'])) {
    $fileOptions['linkAttributes']['target'] = '_blank';
  }

  if (!isset($fileOptions['imgAttributes']['alt'])) {
    $fileOptions['imgAttributes']['alt'] = metadata($item, array('Dublin Core', 'Title'));
  }
    
  if ($file) {
    $html = file_markup($file, $fileOptions, null);
  } else if($item) {
    $html = exhibit_builder_link_to_exhibit_item(null, $linkProperties, $item);
  }

  $html .= exhibit_builder_attachment_caption($attachment);

  if (($mime_type != 'image/jpeg') && ($mime_type != 'image/gif')) {
    // fcd1, 9/9/13: Don't print a link to item page if file is not a jpeg. No need to do this because
    // there will already be a link  in place of the thumbnail
    $link_to_item = 0;
  }

  if ($link_to_item) {
    // fcd1, 8/28/13: add code that creates a link to the item page for this image
		            $html .= '<div class="link-to-item-page">';
    $html .= exhibit_builder_link_to_exhibit_item('Click here for item information',
						  array('class' => 'link-to-item-page',
							// fcd1, 01/16/14:
							// uncomment the following line to have 
							// the item page open in a new tab:
							// 'target' => '_blank'
							),
						  $attachment['item']);
	      $html .='</div>';
  }

  return apply_filters('exhibit_builder_attachment_markup', $html,
		       compact('attachment', 'fileOptions', 'linkProperties')
		       );
}

function cul_exhibit_builder_thumbnail_gallery($start, $end, 
						  $props = array(), $exhibitPage,
						  $thumbnailType = 'square_thumbnail')
{
  // fcd1, 8/30/13: creating a link to the item page should always occur, it cannot be an option
  //  $link_to_item = get_theme_option('Link Item Page');
  $link_to_item = 1;

    $html = '';
    for ($i = (int)$start; $i <= (int)$end; $i++) {
      if ($attachment = exhibit_builder_page_attachment($i,0,$exhibitPage)) {
            $html .= "\n" . '<div class="exhibit-item">';
            if ($attachment['file']) {
		// fcd1, 9/9/11: if file is image, have thumbnail link to image. Else, use the default 
		// behavior.
		$mime_type = metadata($attachment['file'],'mime_type');
		if (($mime_type == 'image/jpeg') || ($mime_type == 'image/gif')) {
		  $thumbnail = file_image($thumbnailType, $props, $attachment['file']);
		  $full_img_file = $attachment['file'];
		  $full_img_url = $full_img_file->getWebPath('original');
		  $html .= '<a href="'. $full_img_url . 
	    '" onclick="return hs.expand(this)" class="highslide" target="_blank">' . 
		    $thumbnail . '</a>';
		} else {
		  // $html .= exhibit_builder_link_to_exhibit_item($thumbnail, array(), $attachment['item']);
		  // fcd1, 9/9/13: if not a jpeg, just use title of item as text in <a>, so pass empty
		  // first parameter, which will cause text to default to item title, and <a> links to
		  // item page
		  $html .= exhibit_builder_link_to_exhibit_item('', array(), $attachment['item']);
		  $link_to_item = 0;
		}

            }
            $html .= exhibit_builder_attachment_caption($attachment);
  
	    	    if ($link_to_item) {
		            $html .= '<div class="link-to-item-page">';
	      // fcd1, 8/28/13: add code that creates a link to the item page for this image
	      $html .= exhibit_builder_link_to_exhibit_item('Click here for item information',
							    array('class' => 'link-to-item-page',
								  // fcd1, 01/16/14:
								  // uncomment the following line to have 
								  // the item page open in a new tab:
								  // 'target' => '_blank'
								  ),
							    $attachment['item']);
	      $html .='</div>';
	    }

          $html .= '</div>' . "\n";
      }
    }
    
    return apply_filters('exhibit_builder_thumbnail_gallery', $html,
        array('start' => $start, 'end' => $end, 'props' => $props, 'thumbnail_type' => $thumbnailType));
}

// fcd1, 8/19/13: RMBL => removed html_escape around page title  to handle embedded <em> in section titles
 // $child_page_render is used to store the first child page in the selected top-level page
 // this is to mimic the omeka 1.5.3. behavior, where, when a section is selected, the content of the first
 // child page is shown. It is passed by reference because the caller needs the value.
function cul_legacy_exhibit_builder_page_nav($exhibitPage = null)
{
    if (!$exhibitPage) {
        if (!($exhibitPage = get_current_record('exhibit_page', false))) {
	  return;
	}
    }

    $exhibit = $exhibitPage->getExhibit();
    $pagesTrail = $exhibitPage->getAncestors();
    $pagesTrail[] = $exhibitPage;
    // fcd1, 8/19/13: update class attributes to match classes used in original code
    $html = '<ul class="exhibit-section-nav">' . "\n";
    // fcd1, 8/8/13: my stuff
    //    $html = '<ul class="cul-general-exhibit-nav-ul">' . "\n";
    $html .= '<li id="cul-general-exhibit-nav-title">';
    $html .= '<a class="exhibit-title" href="'. html_escape(exhibit_builder_exhibit_uri($exhibit)) . '">';
    //    $html .= cul_insert_angle_brackets(html_escape($exhibit->title)) .'</a></li>' . "\n";
    $html .= 'Home</a></li>' . "\n";

    // fcd1, 12Aug13: got rid of the foreach loop, now just unshift page out of pagesTrail array
    // in argument to exhibit_builder_exhibit_uri() call
    //    foreach ($pagesTrail as $page) {
    $page = array_shift($pagesTrail);
        $linkText = $page->title;
        $pageExhibit = $page->getExhibit();
        $pageParent = $page->getParent();
        $pageSiblings = ($pageParent ? exhibit_builder_child_pages($pageParent) : $pageExhibit->getTopPages()); 

	// fcd1, 8/12/13: added class to the ul below
	// fcd1, 8/19/13: update class attributes to match classes used in original code
        $html .= '<li class="precedes-ul-tag">' . "\n" . '<ul class="exhibit-section-nav navigation">' . "\n";
        foreach ($pageSiblings as $pageSibling) {
	  // fcd1, 8/19/13: update class attributes to match classes used in original code
            $html .= '<li class="exhibit-section-title ' . 
	      ($pageSibling->id == $page->id ? 'current"' : '"') . '>';
	  // fcd1, 8/19/13: update class attributes to match classes used in original code
            $html .= '<a class="exhibit-section-title" href="' . 
	      html_escape(exhibit_builder_exhibit_uri($exhibit, $pageSibling)) . '">';
	    // fcd1, 8/19/13: remove html_escape()
	    // $html .= html_escape($pageSibling->title) . "</a></li>\n";
	    $html .= $pageSibling->title . "</a></li>\n";
	    // fcd1, 8/8/13: I added the following two lines (compared to default exhibit_puilder_page_nav()
	    if ($pageSibling->id == $page->id) {
	      if ($pagesTrail) {
		// called when displaying the children (second-level) pages when one of the children pages
		// is the current page
		$html .= fcd1_exhibit_builder_child_page_nav(array_shift($pagesTrail), $pageSibling);
	      }
	      else {
		// fcd1, 8/19/13: called to display the links to the children (second level) pages, 
		// but the used clicked on the link to the parent page
		$html .= cul_legacy_exhibit_builder_child_page_nav();
	      }

	    }
        }
        $html .= "</ul>\n</li>\n";
	//    }
    $html .= '</ul>' . "\n";
    $html = apply_filters('exhibit_builder_page_nav', $html);
    return $html;
}

function cul_legacy_exhibit_builder_child_page_nav($exhibitPage = null)
{
    if (!$exhibitPage) {
        $exhibitPage = get_current_record('exhibit_page');
    }

    $exhibit = $exhibitPage->getExhibit();
    $children = exhibit_builder_child_pages($exhibitPage);
    $html = '<ul class="exhibit-page-nav navigation rbml">' . "\n";
    foreach ($children as $child) {
      $html .= '<li><a class="exhibit-page-title" href="' . 
	html_escape(exhibit_builder_exhibit_uri($exhibit, $child)) . '">' . 
	$child->title . '</a></li>';
    }
    $html .= '</ul>' . "\n";
    return $html;
}

// fcd1, 10/8/13: following function is based on the theme_logo function defined in
// applications/libraries/globals.php. The original method does not set the alt attribute,
// and set the title attribute to the site title. validator.w3.org is not happy that the alt.
// So, instead of hard-coding the title attribute, will set the alt attribute via an argument
function cul_theme_logo($alt_attribute_value)
{
    $logo = get_theme_option('Logo');
    if ($logo) {
        $storage = Zend_Registry::get('storage');
        $uri = $storage->getUri($storage->getPathByType($logo, 'theme_uploads'));
        return '<img src="' . $uri . '" alt="' . $alt_attribute_value . '" />';
    }
}

// fcd1, 10/11/13: Based on exhibit_builder_link_to_next_page in 
// plugins/ExhibitBuilder/helpers/ExhibitPageFunctions.php . This method has a bug,
// so fixed the bug in the following version.
function cul_exhibit_builder_link_to_next_page($text = null, $props = array(), $exhibitPage = null)
{
    if (!$exhibitPage) {
        $exhibitPage = get_current_record('exhibit_page');
    }

    $exhibit = get_record_by_id('Exhibit', $exhibitPage->exhibit_id);

    $targetPage = null;

    // if page object exists, grab link to the first child page if exists. If it doesn't, grab
    // a link to the next page
    if ($nextPage = $exhibitPage->firstChildOrNext()) {
        $targetPage = $nextPage;
    } elseif ($exhibitPage->parent_id) {
        $parentPage = $exhibitPage->getParent();
        $nextParentPage = $parentPage->next();
        // fcd1, 10/11/13: Fix Omeka bug: if $parentPage above was a second-level page, 
	// with no sibling following, it would assume there was no next page. However, there
	// may be a top-level page that follows, does not check for this
	if (!$nextParentPage) {
	  $topParentPage = $parentPage->getParent();
	  if ($topParentPage) {
	    $nextTopParentPage = $topParentPage->next();
	    $targetPage = $nextTopParentPage;
	  }
	  // $targetPage = $topParentPage;
	} else {
	  $targetPage = $nextPage;
        }
    }

    if ($targetPage) {
        if (!isset($props['class'])) {
            $props['class'] = 'next-page';
        }
        if ($text === null) {
            $text = metadata($targetPage, 'title') . ' &rarr;';
        }
        return exhibit_builder_link_to_exhibit($exhibit, $text , $props, $targetPage);
    }

    return null;
}

function culWritePrevNext($nextPage = NULL) {
  $ret = '<div class="prevNext">';
  $prev = exhibit_builder_link_to_previous_page('&larr; Previous Page');
  $printed = false;

  if (!is_null($prev)) {
    $printed = true;
    $ret .= $prev;
  }

  $next = cul_exhibit_builder_link_to_next_page('Next Page &rarr;',array(),$nextPage);

  if (!is_null($next)) {
    if ($printed)
      $ret .= " | ";
    $ret .= $next;
  }
  $ret .= "</div>\n";
  return $ret;
}

function cul_insert_angle_brackets($in) {
  return str_replace("&gt;", ">", str_replace("&lt;", "<",$in));
}

function cul_copyright_information() {
  $html =
    '<a target="_blank" href=' .
    '"http://library.columbia.edu/about/policies/copyright-online-collections.html"' .
    ">Copyright & Permissions</a>";
  return $html;

}

function cul_display_links_to_exhibit_pages_containing_item() {

  $item = get_current_record('item');
  $db = get_db();

  $select = "                                                                                                        
    SELECT ep.* FROM {$db->prefix}exhibit_pages ep
    INNER JOIN {$db->prefix}exhibit_page_entries epe ON epe.page_id = ep.id
    WHERE epe.item_id = ?";

  $exhbit = NULL;
  $exhibit_pages = $db->getTable("ExhibitPage")->fetchObjects($select,array($item->id));
  
  if(!empty($exhibit_pages)) {
    echo '<div class="list-exhibit-pages">';
    echo '<h3>Item appears in the following exhibit pages</h3>';
    foreach($exhibit_pages as $exhibit_page) {
      $exhibit = $exhibit_page->getExhibit();
      echo '<p><a href="'.html_escape(exhibit_builder_exhibit_uri($exhibit, $exhibit_page
								  )).'">'.$exhibit->title.': '.$exhibit_page->title.'</a></p>';
    }
  }
  echo '</div>';
}
?>