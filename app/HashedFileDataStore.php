<?php

/**
 * This class abstract the data access to the repository
 */
class Plugin_HashedFileDataStore extends Plugin_SimpleFileDataStore {

  protected $nonHashedTypes = ['document', 'courrier', 'avatar'];

  public function __construct($options = null) {

    parent::__construct($options);

    $this->types = ['dateCommission' => 'commission'];

    $this->format = '%NOM_PIECEJOINTE%%EXTENSION_PIECEJOINTE%';
  }

  public function getBasePath($piece_jointe, $linkedObjectType, $linkedObjectId): string
  {
    if (in_array($linkedObjectType, $this->nonHashedTypes)) {
      return parent::getBasePath($piece_jointe, $linkedObjectType, $linkedObjectId);
    }

    $type = $this->types[$linkedObjectType] ?? $linkedObjectType;

    $hash = md5($piece_jointe['ID_PIECEJOINTE']);

    return implode(DS, [REAL_DATA_PATH, 'uploads', $type, substr($hash, 0, 2), substr($hash, 2, 2), $linkedObjectId]);
  }

  public function getURLPath($piece_jointe, $linkedObjectType, $linkedObjectId): ?string {
    if ($piece_jointe === []) {
      return null;
    }

    $type = $this->types[$linkedObjectType] ?? $linkedObjectType;

    $hash = md5($piece_jointe['ID_PIECEJOINTE']);
    return implode('/', [DATA_PATH, 'uploads', $type, substr($hash, 0, 2), substr($hash, 2, 2), $linkedObjectId, $piece_jointe['ID_PIECEJOINTE'].$piece_jointe['EXTENSION_PIECEJOINTE']]);
  }
}