<?php
/**
 * @file
 * Migrate class for news from INSTANS CMS to Panopoly news feature.
 */
class MigrateNews extends Migration {
  public function __construct() {
    parent::__construct();
    $this->description = t('Migrate news from INSTANS CMS NEWS table to Panopoly news feature');
    $this->team = array(
      new MigrateTeamMember('Lars Olesen', 'lars@vih.dk', t('Webmaster')),
    );

    $query = db_select('NEWS', null)
             ->fields('NEWS', array('ID', 'HEADING', 'CONTENT', 'CREATED_DATE', 'CHANGED_DATE'))
             ->where('DELETED = :deleted', array(':deleted' => 0));

    $this->source = new MigrateSourceSQL($query);
    $this->destination = new MigrateDestinationNode('panopoly_news_article');
    $this->map = new MigrateSQLMap($this->machineName,
      array(
        'ID' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        )
      ),
      MigrateDestinationNode::getKeySchema()
    );
    $this->addFieldMapping('title', 'HEADING');
    $this->addFieldMapping('body', 'CONTENT')
         ->arguments(array('format' => 'panopoly_wysiwyg_text'));
    $this->addFieldMapping('uid')
         ->defaultValue(1);
    $this->addFieldMapping('created', 'CREATED_DATE');
    $this->addFieldMapping('changed', 'CHANGED_DATE');
  }
  /*
  function complete($node, $row) {
    $html = str_get_html($node->body[LANGUAGE_NONE][0]['value']);

    // Parse our body content and update the image uuid paths with
    // local files managed by media module.
    $total_img = count($html->find("img"));

    // If we have img tags.
    if ($total_img > 0) {
      // Loop over all instances of them.
      for ($i = 0; $i < $total_img; $i++) {

        // Find out if the img tag contains a legacy plone uid path.
        $src = explode('/', $html->find("img", $i)->src);
        // It does. We'll replace it.
        if (strtolower($src[0]) == 'resolveuid') {

          // We have a uuid - we'll try to load our local entity using that.
          $files = entity_uuid_load("file", array(normalize_uuid($src[1])));
          $local_img = current($files);
          $local_path = $local_img->uri;

          // There is no local image.
          if (empty($local_img)) {
            watchdog('tws_plone', 'Local image not found for UUID ' . $src[1], WATCHDOG_ERROR);
          }
          else {
            // We have a local image. We'll update the src.
            $html->find("img", $i)->src = $local_path;
          }
        }
      }

      // We'll update the body field with the new one.
      $node->body[LANGUAGE_NONE][0]['value'] = $html->save();
      // We'll convert all 'img' tags to media references.
      MigrateDestinationMedia::rewriteImgTags($node, 'body');

      file_usage_add($local_img, 'file', 'node', $node->nid);

      node_save($node);
    }
  }
  */
}
