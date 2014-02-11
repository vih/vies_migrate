<?php
/**
 * @file
 * Migrate class for news from INSTANS CMS to Panopoly news feature.
 */
include_once 'simple_html_dom.php';

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
  
  function __prepare($row) {
    $row->language = 'da';
  }
  
  function complete($entity, $row) {
    $entity->uid = 184; // Set Jacob as author
    $html = str_get_html($entity->body[LANGUAGE_NONE][0]['value']);
    
    if (!$html) {
      return;
    }

    // Parse our body content and update the image uuid paths with
    // local files managed by media module.
    $total_img = count($html->find("img"));

    // If we have img tags.
    if ($total_img > 0) {

      // Loop over all instances of them.
      for ($i = 0; $i < $total_img; $i++) {
        $featured_image = $html->find("img", $i)->src;
        
        /*
        $this->source = new MigrateSourceSQL($query);
        $this->destination = new MigrateDestinationFile('file', 'MigrateFileBlob');
        $this->addFieldMapping('value', 'imageblob');
        $this->addFieldMapping('destination_file', 'filename');
        $this->addFieldMapping('uid', 'file_ownerid')
             ->sourceMigration('User');
        
        $this->addFieldMapping('value', 'filename');
        $this->addFieldMapping('source_dir')
             ->defaultValue('/mnt/files');
        $this->addFieldMapping('destination_file', 'filename');
        */
        // Breaks only for first image. Could we do something with the other images? 
        break;
      }
      /*
      // We'll update the body field with the new one.
      $node->body[LANGUAGE_NONE][0]['value'] = $html->save();
      // We'll convert all 'img' tags to media references.
      MigrateDestinationMedia::rewriteImgTags($node, 'body');

      file_usage_add($local_img, 'file', 'node', $node->nid);

      node_save($node);
      */
      $directory = file_build_uri('public://migrated_files');
      if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
        $directory = NULL;
      }
      $file = system_retrieve_file($featured_image, $directory, TRUE, FILE_EXISTS_RENAME);
      if ($file) {
        print 'succes for ' . $entity->nid . "\n";
        $entity->field_featured_image[LANGUAGE_NONE][0]['fid'] = $file->fid;
        node_save($entity);
      }
    }
  }
}
