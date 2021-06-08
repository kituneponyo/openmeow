<?php
/**
 * Created by PhpStorm.
 * User: maju
 * Date: 2021/06/09
 * Time: 6:33
 */

namespace Meow\ActivityPub;

use Meow\ActivityPub\Object\ActivityPubObject;

class Collection extends \LibraryBase
{
	public static function load (string $collection) {
		$sql = " select id from ap_collection where collection = ? ";
		return self::db()->query($sql, [$collection])->row();
	}

	public static function getCollectionRowId (string $collection) {
		if ($row = self::load($collection)) {
			return $row->id;
		}
		self::db()->insert('ap_collection', ['collection' => $collection]);
		return self::db()->insert_id();
	}

	public static function add (string $target, string $object) {

		$collectionRowId = self::getCollectionRowId($target);
		$objectRowId = \ActivityPubService::getActivityPubObjectRowId($object);

		$sql = " select id from ap_collection_object where collection_id = ? and object_id = ? ";
		$row = self::db()->query($sql, [$collectionRowId, $objectRowId])->row();
		if (!$row) {
			$values = [
				'collection_id' => $collectionRowId,
				'object_id' => $objectRowId,
			];
			self::db()->insert('ap_collection_object', $values);
		}
	}

	public static function remove ($target, $object) {
		$collection = self::load($target);
		$apObject = ActivityPubObject::load($object);
		if ($collection && $apObject) {
			$sql = " delete from ap_collection_object where collection_id = ? and object_id = ? ";
			self::db()->query($sql, [$collection->id, $apObject->id]);
		}
	}
}