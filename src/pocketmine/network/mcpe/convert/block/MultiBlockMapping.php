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

namespace pocketmine\network\mcpe\convert\block;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\utils\ProtocolUtils;

final class MultiBlockMapping {

	/** @var BlockMapping[] */
	private static $mapping = null;

	protected static function init(int $protocol) : void {
		$protocol = ProtocolUtils::convertToBlockMapping($protocol);

		if(!isset(self::$mapping[$protocol])) {			
			self::$mapping[$protocol] = new BlockMapping($protocol);
		}
	}
	
	public static function toStaticRuntimeId(int $id, int $meta = 0, int $protocol = ProtocolInfo::CURRENT_PROTOCOL) : int {
		self::init($protocol);
		return self::$mapping[ProtocolUtils::convertToBlockMapping($protocol)]->toStaticRuntimeId($id, $meta);
	}

	/**
	 * @return int[] [id, meta]
	 */
	public static function fromStaticRuntimeId(int $runtimeId, int $protocol = ProtocolInfo::CURRENT_PROTOCOL) : array {
		self::init($protocol);
		return self::$mapping[ProtocolUtils::convertToBlockMapping($protocol)]->fromStaticRuntimeId($runtimeId);
	}

	/**
	 * @return CompoundTag[]
	 */
	public static function getBedrockKnownStates(int $protocol = ProtocolInfo::CURRENT_PROTOCOL) : array {
		self::init($protocol);
		return self::$mapping[ProtocolUtils::convertToBlockMapping($protocol)]->getBedrockKnownStates();
	}

	/**
	 * @return string
	 */
	public static function getBedrockKnownStatesRaw(int $protocol = ProtocolInfo::CURRENT_PROTOCOL) : string {
		self::init($protocol);
		return self::$mapping[ProtocolUtils::convertToBlockMapping($protocol)]->getBedrockKnownStatesRaw();
	}

}