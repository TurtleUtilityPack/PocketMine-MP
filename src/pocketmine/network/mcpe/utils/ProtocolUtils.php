<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\utils;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use function file_get_contents;

final class ProtocolUtils {

	/** @var string[] */
	private static $BIOME_DEFINITIONS_CACHE = [];

	/** @var string[] */
	private static $ENTITY_IDENTIFIERS_CACHE = [];

	/**
	 * @param int $protocol
	 * @return string
	 */
	public static function getBiomeDefinitions(int $protocol = ProtocolInfo::CURRENT_PROTOCOL) : string {
		$protocol = self::convert($protocol);

		if(!isset(self::$BIOME_DEFINITIONS_CACHE[$protocol])) {
			self::$BIOME_DEFINITIONS_CACHE[$protocol] = file_get_contents(\pocketmine\RESOURCE_PATH.'/vanilla/'.$protocol.'/biome_definitions.nbt');
		}
		return self::$BIOME_DEFINITIONS_CACHE[$protocol];
	}

	/**
	 * @param int $protocol
	 * @return string
	 */
	public static function getEntityIdentifiers(int $protocol = ProtocolInfo::CURRENT_PROTOCOL) : string {
		$protocol = self::convert($protocol);

		if(!isset(self::$ENTITY_IDENTIFIERS_CACHE[$protocol])) {
			self::$ENTITY_IDENTIFIERS_CACHE[$protocol] = file_get_contents(\pocketmine\RESOURCE_PATH.'/vanilla/'.$protocol.'/entity_identifiers.nbt');
		}
		return self::$ENTITY_IDENTIFIERS_CACHE[$protocol];
	}

	/**
	 * @param int $protocol
	 * @return int
	 */
	public static function convert(int $protocol) {
		if($protocol >= ProtocolInfo::PROTOCOL_419) {
			return ProtocolInfo::PROTOCOL_419;
		}
		elseif($protocol >= ProtocolInfo::PROTOCOL_407) {
			return ProtocolInfo::PROTOCOL_407;
		}
		return ProtocolInfo::MINIMAL_PROTOCOL;
	}

	/**
	 * @param int $protocol
	 * @return int
	 */
	public static function convertToBlockMapping(int $protocol) {
		if($protocol >= ProtocolInfo::PROTOCOL_428) {
			return ProtocolInfo::PROTOCOL_428;
		}
		return self::convert($protocol);
	}

	/**
	 * @param int $protocol
	 * @return int
	 */
	public static function convertToChunk(int $protocol) {
		if($protocol >= ProtocolInfo::PROTOCOL_407) {
			return ProtocolInfo::PROTOCOL_407;
		}
		return ProtocolInfo::PROTOCOL_389;
	}

	/**
	 * @param int $protocol
	 * @return int
	 */
	public static function convertToEatingData(int $protocol) {
		if($protocol >= ProtocolInfo::PROTOCOL_419) {
			return ProtocolInfo::PROTOCOL_419;
		}
		return ProtocolInfo::PROTOCOL_389;
	}

	/**
	 * @param int $protocol
	 * @return int
	 */
	public static function convertToCrafting(int $protocol) {
		if($protocol >= ProtocolInfo::PROTOCOL_431) {
			return ProtocolInfo::PROTOCOL_431;
		}
		return self::convert($protocol);
	}

}