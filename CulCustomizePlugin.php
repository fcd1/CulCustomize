<?php

class CulCustomizePlugin extends Omeka_Plugin_AbstractPlugin
{

  protected $_filters = array('admin_items_form_tabs',
			      'collections_select_options',
			      'exhibit_attachment_markup',
			      'browse_themes',
			      'item_citation');

  protected $_hooks = array('admin_head',
			    'admin_items_show_sidebar',
			    'initialize',
			    'edit_exhibit_metadata');

  public function filterItemCitation($citation, $array_arg)
  {
    // The definition of getCitation in models/Item.php sets the url given in the citation to the url
    // of the item record. However, we want it to point to the item page within the exhibition.
    // Since the citation is being built within the view of the item page within the exhibition,
    // current_url will return the needed url. In the case of the generic item record page (not within
    // the context of an exhibition), this will also work since the view will be the item record view, so
    // current_url will equal record_url
    $pattern = '/' . preg_quote( record_url( $array_arg['item'], null, true ), '/' ) . '/';
    $replacement = absolute_url( current_url() );
    return preg_replace($pattern,$replacement,$citation);
  }

  public function filterBrowseThemes($all_themes)
  {

    $user = current_user();
    $role_current_user = $user->role;
    $filtered_themes = array();

    if ( ($role_current_user == 'super') || ($role_current_user == 'admin') ):

      // set CUL General theme as the first choice
      $filtered_themes = $all_themes;
      unset($filtered_themes['cul-general']);
      $filtered_themes = array('cul-general' => $all_themes['cul-general']) + $filtered_themes;

    else:

      // for users of type other than admin or super, only offer cul-general as an option,
      // as well as the current theme for the exhibit.

      // set CUL General theme as the first choice
      $filtered_themes['cul-general'] = $all_themes['cul-general'];

      $exhibit = get_current_record('exhibit',  false);
      $exhibit_theme_name = null;

      if ($exhibit) {
	
	$exhibit_theme_name = $exhibit->theme;

      }

      if ($exhibit_theme_name) {

	$filtered_themes[$exhibit_theme_name] = $all_themes[$exhibit_theme_name];
	
      }
      
    endif;

    return $filtered_themes;

  }

  public function hookEditExhibitMetadata($args)
  {

    $current_user = $args['user'];
    $view = $args['view'];
    $exhibit = $args['exhibit'];

    $role_current_user = $current_user->role;
    $html = '';

    if ( ($role_current_user == 'super') || ($role_current_user == 'admin') ):
      $html =  '<div class="field">';
      $html .=  '<div class="two columns alpha">';
      $html .= $view->formLabel('owner_id', __('Owner'));
      $html .= '</div>';
      $html .= '<div class="five columns omega inputs">';
      $values = get_table_options('User');
      $user_table = get_db()->getTable('User');

      foreach ($values as $key => $value) {

	$user = $user_table->findActiveById($key);

	if ($user) {

	  $values[$key] = $value . ' (' . $user->username . ')';

	}

	else {

	  if ($value != 'Select Below ') {

	    $values[$key] = $value . ' - NOTE: User is inactive.';
                
	  }

	}

      }

      if (!$exhibit->owner_id) {

	// fcd1, 05/20/15: Since $exhibit->owner_id is not set, we will set it to the current user
	$exhibit->owner_id = current_user()->id;
	
      }

      // following contains optional message
      $msg = "";

      // fcd1, 05/19/15:
      // if the owner_id contains the user_id of a user that has been removed from the system, print
      // a message stating that fact, and set the owner to the current logged-in user.
      if ( !array_key_exists($exhibit->owner_id, $values) ) {
	
	$msg .= '<p class="instructions">' . 
	  'Owner was originally set to nonexistent user (user_id: ' . $exhibit->owner_id . ').';
	$msg .= '<br>Owner has been reset to the currently logged-in user.';
	$msg .= '<br>Select a new owner if appropriate.</p>';
	$exhibit->owner_id = current_user()->id;	
      }

      $html .= $view->formSelect('owner_id', $exhibit->owner_id, array(), $values);
      $html .= $msg;

      $html .= '</div>';
      $html .= '</div>';

    endif;

    echo $html;

  }

  public function hookAdminItemsShowSidebar($args)
  {

    $role_current_user = current_user()->role;
    if ( ($role_current_user == 'super') || ($role_current_user == 'admin') ):
      
      $item = $args['item'];
      $owner_id = $item->owner_id;
      $user_table = get_db()->getTable('User');
      $owner = $user_table->find($owner_id);
      $html = '<div class="panel">';
      $html .="<h4>Owner of item in Omeka</h4>";

      if ($owner):

        $html .= "<p>" . $owner->name;
        $html .= "<br>";
        $html .= "Omeka username: " . $owner->username . "</p>";

      else:

	$html .= "<p>No owner defined</p>";
	$html .="<p>This can happen if user account who owned item was deleted from Omeka.</p>";

      endif;

      $html .= '</div>';
      echo $html;       

    endif;

  }

  public function hookInitialize() {
    
    add_file_display_callback(array('mimeTypes' => array('image/jpeg','image/png','image/tiff'),
				    'fileExtensions' => array('jpeg','jpg') ),
			      'CulCustomizePlugin::fileHighslideCompatible' );

  }

  public static function fileHighslideCompatible($file, $options) {

    //fcd1, 01/02/15: Following was copied from derivativeImage() defined in
    // views/helpers/FileMarkup.php, then modified
    // BEGIN>>>>>>
    // Should we ever include more image sizes by default, this will be                                                                                
    // easier to modify.                                                                                                                               
    $imgClasses = array(
			'thumbnail'=>'thumb',
			'square_thumbnail'=>'thumb',
			'fullsize'=>'full');

    if (array_key_exists('imageSize', $options)) {

      $imageSize = $options['imageSize'];

    }
    
    else {

      $imageSize = 'thumbnail';

    }

    // If we can make an image from the given image size.                                                                                              
    if (in_array($imageSize, array_keys($imgClasses))) {

      // A class is given to all of the images by default to make it                                                                                 
      // easier to style. This can be modified by passing it in as an                                                                                
      // option, but recommended against. Can also modify alt text via an                                                                            
      // option.                                                                                                                                     
      $imgClass = $imgClasses[$imageSize];

      if (array_key_exists('imgAttributes', $options)) {

        $imgAttributes = array_merge(array('class' => $imgClass),
				     (array)$options['imgAttributes']);

      }
      else {

        $imgAttributes = array('class' => $imgClass);

      }
      //<<<<<<END
    }
    $imgHtml = '<img src="' . html_escape($file->getWebPath($imageSize)) . '" ' . tag_attributes($imgAttributes) . '/>';

    $mime_type = metadata($file,'mime_type');

    if ( $mime_type == 'image/jpeg' ) {

      $href_to_image = $file->getWebPath('original');

    } 
    else {

      // fcd1, 07Apr15: Having issues with tiffs, maybe should link to fullsize (which is a derivative jpeg)
      // instead of original
      $href_to_image = $file->getWebPath('fullsize');

    }

    $highslide_stuff = 'class="highslide" onclick="return hs.expand(this)" target="_blank"';

    $html = '<a href="' . $href_to_image . '" ' . $highslide_stuff . '>' . $imgHtml . '</a>';

    return $html;

  }

  public function filterExhibitAttachmentMarkup($html,$other_stuff)
  {

    $attachment = $other_stuff['attachment'];
    $file = $attachment->getFile();

    if (!$file) {
      return $html;
    }

    // fcd1, 06/18/15: Hide Link Item Page is a checkbox in the cul-general theme config page
    // NOTE: If the option does not exist (which is the case for all the other themes),
    // get_theme_option returns null, and the default behavior takes over, which is to show the link.
    // fcd1, 07/09/15: After further discussion, it was decided that this option will not be offered.
    // So $link_to_item will always be set to 1.
    // If this option is enabled in the future, just use its value to set $link_to_item, as is done
    // in the commented-out code below
    /*
    if ( get_theme_option('Hide Link Item Page') ) {
      $link_to_item = 0;
    }
    else {
      $link_to_item = 1;
    }
    */
    $link_to_item = 1;

    $mime_type = metadata($file,'mime_type');

    // fcd1, 04/08/15: Seems the callback registered via add_file_display_callback is not being called
    // for thumbnails. So I need to add the highslide information here for images.
    if ( ( $mime_type == 'image/jpeg' )
	 ||
	 ( $mime_type == 'image/png' )
	 ||
	 ( $mime_type == 'image/tiff' ) ) {

      $href_to_image = $file->getWebPath('original');
      $new_href_and_highslide_info = 'href="' . $href_to_image . '" onclick="return hs.expand(this)" target="_blank"';
      $html = preg_replace('/href="([a-zA-Z0-9\/\-\.\_]+)"/',$new_href_and_highslide_info,$html);
      // fcd1, 04/20/15: Following assumes there is a class="exhibit-item-link" attribute
      // in the html being filetered
      $html = str_replace('exhibit-item-link','highslide',$html);      
      // fcd1, 04/14/15: debugging code, print out mime type
      // $html .= '<p>' . $mime_type . '</p>';      

    }
    else {

      // ExhibitBuilder code has the href point to the item page. We want to point it to the original file
      $href_to_image = 'href="' . $file->getWebPath('original') . '"';
      $html = preg_replace('/href="([a-zA-Z0-9\/\-\.\_]+)"/',$href_to_image,$html);

    }

    if ($link_to_item) {
      $html .= exhibit_builder_link_to_exhibit_item('Click here for item information',
						    array('class' => 'link-to-item-page',
							  // fcd1, 01/16/14:
							  // uncomment the following line to have
							  // the item page open in a new tab:
							  // 'target' => '_blank'
							  ),
						    $attachment->getItem());
    }

    return $html;

  }

  // fcd1, 9/22/14: Found out about following filter via
  // omeka.org/forums/topic/re-ordering-collections-alphabetically-in-the-items-page,
  // This filter is called by get_table_options in libraries/globals.php
  // The implementation below will alphabetize the list of collections.
  // We want this so that the options under "Search by Collection"
  // in the Search Items page (which we get when Advanced Search is
  // selected) will be in alphabetical order.
  public function filterCollectionsSelectOptions($options)
  {
    asort($options);
    return $options;
  }

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

  public function hookAdminHead() {
    queue_css_file('culAdmin');
  }

  public static function display_links_to_exhibit_pages_containing_item($exhibit_pages,
									$exhibit_page_containing_item) {

    if(empty($exhibit_pages)) {
      // No exhibit pages to link to, so just return
      return;
    }
    $html_output_links = '';
    $number_of_links = 0;

    if ($exhibit_page_containing_item) {
      // Since there is a link to the exhibit page we came from, use
      // the world "also" in the heading for the other links
      $also = 'also';
    } else {
      $also = '';
    }
    // First, generate the code for the links. Keep track of number of links
    foreach($exhibit_pages as $exhibit_page) {
      if ($exhibit_page !== $exhibit_page_containing_item) {
	$exhibit = $exhibit_page->getExhibit();

	// fcd1, 06/01/15: It seems that $exhibit_page->getExhibit() will return null if the exhibit
	// containing the exhibit page is not public AND the access is not being made via a logged-in
	// user with access to non-public exhibits. So added check on $exhibit .
	// fcd1, 03/10/14:
	// Don't display link to private exhibit
	if ( isset($exhibit) && ( $exhibit->public == 1 ) ) {
	  $html_output_links .= '<p><a href="';
	  $html_output_links .= html_escape(exhibit_builder_exhibit_uri($exhibit, $exhibit_page));
	  $html_output_links .= '">'.$exhibit->title.': ';
	  $parent_page = $exhibit_page->getParent();
	  $html_output_links .= ( $parent_page ? $parent_page->title . ': ' : '');
	  $html_output_links .= $exhibit_page->title.'</a></p>';
	  $number_of_links++;
	}
      }
    }
    // fcd1, 01/30/14:
    // Generate html for the div and heading preceding the list of links
    // Of course, only do this if there a links to display
    if ($number_of_links) {
      echo '<div class="list-exhibit-pages">';
      if ($number_of_links == 1) {
	echo '<h3>Item ' . $also . ' appears in the following exhibition page</h3>';
      } else {
	echo '<h3>Item ' . $also . ' appears in the following exhibition pages</h3>';
      }
      echo $html_output_links;
      echo '</div>';
    }
  }

  public static function return_exhibit_pages_containing_current_item(Item $item = NULL) {

    if (!$item) {
      $item = get_current_record('item');
    }

    $db = get_db();

    $select = "
      SELECT ep.* FROM {$db->prefix}exhibit_pages ep
      INNER JOIN {$db->prefix}exhibit_page_blocks epb ON epb.page_id = ep.id
      INNER JOIN {$db->prefix}exhibit_block_attachments eba ON eba.block_id = epb.id
      WHERE eba.item_id = ?";

    $exhibit_pages = $db->getTable("ExhibitPage")->fetchObjects($select,array($item->id));

    return $exhibit_pages;
  }

  // fcd1, 01/28/14:
  // This function is used in exhibit-builder/exhibits/item.php
  // This function takes an array of pages containing an element,
  // and figures out, best as it can, if one of them is the page which
  // was used to get to the item page.
  // If not page was found, return NULL
  // Unfortunately, from the item page, there is no way
  // to get the exhibit page we came from - get_current_record('exhibit_page')
  // returns an error. So, instead, we will see if the link to the previous
  // page ($_SERVER['HTTP_REFERER']) is a link to one of the pages
  // which contains the item. This may not always be the case, since it is
  // possible that the user arrived on the item page via a web search. In
  // this case, do not print a link back to the exhibit page.
  public static function return_exhibit_page_to_link_back_to($exhibit_pages,
                                                             $bool_this_is_a_legacy_exhibit = NULL) {

    if ( !($exhibit_pages) ) {
      return NULL;
    }

    // Return NULL if PHP has no record of a referring page
    if ( !(array_key_exists('HTTP_REFERER',$_SERVER)) ) {
      return NULL;
    }

    // First, check to see if $http_previous contains server uri. If not,
    // no need to continue because the user did not get to the item page
    // via an exhibition. Possible scenario is that user got here via the
    // results of a web search
    $http_previous = $_SERVER['HTTP_REFERER'];
    if ( !(strstr($http_previous, WEB_ROOT)) ) {
      return NULL;
    }

    $exhibit_page_containing_item = NULL;

    // Check all the pages containing the item to see if we get a match
    foreach($exhibit_pages as $exhibit_page) {
      $exhibit = $exhibit_page->getExhibit();
      // fcd1, 06/11/15: $exhibit_page->getExhibit() will return null if the exhibit
      // containing the exhibit page is not public AND the access is not being made
      // via a logged in user with access to non-public exhibits. Of course, if 
      // $exhibit is null, just skip this iteration in the foreach loop.
      if (!$exhibit) {
	continue;
      }
      if (strstr($http_previous,exhibit_builder_exhibit_uri($exhibit, $exhibit_page))) {
	$exhibit_page_containing_item = $exhibit_page;
	break;
      }
    }
    // fcd1, 01/27/14:
    // add the following test for legacy exhibitions (from Omeka 1.5.3)
    // because the previous page may be a top-level page which displays the contents
    // of the first child page, to mimic the behavior of sections in Omeka 1.5.3
    // Only do this if the page was not matched in the above foreach
    if ( !($exhibit_page_containing_item) && $bool_this_is_a_legacy_exhibit) {
      foreach($exhibit_pages as $exhibit_page) {
	$exhibit = $exhibit_page->getExhibit();
	if (strstr($http_previous,exhibit_builder_exhibit_uri($exhibit,$exhibit_page->getParent() ) ) ) {
	  $exhibit_page_containing_item = $exhibit_page;
	  break;
	}
      }
    }    
    // $exhibit_page_containing_item will now either contain the exhibit page used to get to the item page,
    // or it will be NULL.
    return $exhibit_page_containing_item;
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

function cul_general_get_unit_contact($libraryUnit = NULL)
{

  if ($libraryUnit) {
    $unit = $libraryUnit;
  }
  else {
    $unit = get_theme_option('Library Unit');
  }

  if (! isset($unit) or $unit == "")
    return "";

  $unit_contact = "";

  // added by fcd1, 2/25/15: Add generic CUL entry
  if ($unit == "cul")
    $unit_contact = 'Columbia University Libraries  / Butler Library  / 535 West 114th St. / New York, NY 10027 / (212) 854-7309 / <a href="mailto:lio@libraries.cul.columbia.edu">info@libraries.cul.columbia.edu</a>';

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

// fcd1, 12/18/14: The following helper function basically just wraps a call
// to files_for_item. Since all themes called files_for_items with the same
// arguments, it is easier to wrap the funciton call in this helper
// function, and have the themes call the helper function. In case we need to
// add/change an argument to the files_for_item function call, we just have to
// change it here, instead of having to do it in each theme.
function cul_files_for_item() {

  $item = get_current_record('item');
  $imgTitleAndAlt = metadata($item, array('Dublin Core', 'Title'));
  // fcd1, 01/16/14:
  // change imageSize from thumbnail (as set in original code) to fullsize
  echo files_for_item(array('imageSize' => 'fullsize',
			    'imgAttributes' => array('title' => $imgTitleAndAlt,
						     'alt' => $imgTitleAndAlt)
			    )
		      );

}
 
?>