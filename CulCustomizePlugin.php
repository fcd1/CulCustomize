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
?>