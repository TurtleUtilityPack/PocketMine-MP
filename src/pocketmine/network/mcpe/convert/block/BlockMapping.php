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

use Exception;
use pocketmine\block\BlockIds;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\AssumptionFailedError;
use function file_get_contents;
use function json_decode;

final class BlockMapping {

	/** @var int[] */
	private $legacyToRuntimeMap = [];
	/** @var int[] */
	private $runtimeToLegacyMap = [];
	/** @var CompoundTag[]|null */
	private $bedrockKnownStates = null;
	/** @var string */
	private $bedrockKnownStatesRaw = '';
	/** @var int */
	private $protocol;

	public function __construct(int $protocol = ProtocolInfo::CURRENT_PROTOCOL) {
		$this->protocol = $protocol;
		$this->init();
	}

	/**
	 * @return int
	 */
	public function getProtocol() : int {
		return $this->protocol;
	}

	public function init() : void {
		$path = \pocketmine\RESOURCE_PATH.'vanilla/'.$this->protocol.'/canonical_block_states.nbt';
		$this->bedrockKnownStatesRaw = $canonicalBlockStatesFile = file_get_contents($path);

		if($canonicalBlockStatesFile === false) {
			throw new AssumptionFailedError("Missing required resource file");
		}
		$bedrockKnownStates = [];

		try {
			$stream = new NetworkBinaryStream($canonicalBlockStatesFile);

			while(!$stream->feof()){
				$bedrockKnownStates[] = $stream->getNbtCompoundRoot();
			}
		} catch(Exception $exception) {
			$bedrockKnownStates = (new NetworkLittleEndianNBTStream())->read($canonicalBlockStatesFile)->getValue();
		}
		
		$this->bedrockKnownStates = $bedrockKnownStates;
		$this->setupLegacyMappings();
	}

	private function setupLegacyMappings() : void {
		$mainPath = \pocketmine\RESOURCE_PATH.'vanilla/'.$this->protocol.'/';
		$legacyIdMap = json_decode(file_get_contents($mainPath.'block_id_map.json'), true);

		/** @var R12ToCurrentBlockMapEntry[] $legacyStateMap */
		$legacyStateMap = [];
		$legacyStateMapReader = new NetworkBinaryStream(file_get_contents($mainPath.'r12_to_current_block_map.bin'));
		$nbtReader = new NetworkLittleEndianNBTStream();
		while(!$legacyStateMapReader->feof()){
			$id = $legacyStateMapReader->getString();
			$meta = $legacyStateMapReader->getLShort();

			$offset = $legacyStateMapReader->getOffset();
			$state = $nbtReader->read($legacyStateMapReader->getBuffer(), false, $offset);
			$legacyStateMapReader->setOffset($offset);
			if(!($state instanceof CompoundTag)){
				throw new \RuntimeException("Blockstate should be a TAG_Compound");
			}
			$legacyStateMap[] = new R12ToCurrentBlockMapEntry($id, $meta, $state);
		}

		/**
		 * @var int[][] $idToStatesMap string id -> int[] list of candidate state indices
		 */
		$idToStatesMap = [];
		foreach($this->bedrockKnownStates as $k => $state) {
			if($this->protocol < ProtocolInfo::PROTOCOL_419) {
				$idToStatesMap[$state->getCompoundTag("block")->getString("name")][] = $k;
			} else {
				$idToStatesMap[$state->getString("name")][] = $k;
			}
		}
		foreach($legacyStateMap as $pair) {
			$id = $legacyIdMap[$pair->getId()] ?? null;
			if($id === null){
				throw new \RuntimeException("No legacy ID matches " . $pair->getId());
			}
			$data = $pair->getMeta();
			if($data > 15){
				//we can't handle metadata with more than 4 bits
				continue;
			}
			$mappedState = $pair->getBlockState();

			//TODO HACK: idiotic NBT compare behaviour on 3.x compares keys which are stored by values
			$mappedState->setName(($this->protocol >= ProtocolInfo::PROTOCOL_419) ? "" : "block");
			$mappedName = $mappedState->getString("name");
			if(!isset($idToStatesMap[$mappedName])){
				throw new \RuntimeException("Mapped new state does not appear in network table");
			}
			foreach($idToStatesMap[$mappedName] as $k){
				$networkState = $this->bedrockKnownStates[$k];
				if($mappedState->equals(($this->protocol >= ProtocolInfo::PROTOCOL_419) ? $networkState : $networkState->getCompoundTag('block'))){
					$this->registerMapping($k, $id, $data);
					continue 2;
				}
			}
			throw new \RuntimeException("Mapped new state does not appear in network table");
		}
	}

	public function toStaticRuntimeId(int $id, int $meta = 0) : int {
		return $this->legacyToRuntimeMap[($id << 4) | $meta] ?? $this->legacyToRuntimeMap[$id << 4] ?? $this->legacyToRuntimeMap[BlockIds::INFO_UPDATE << 4];
	}

	/**
	 * @return int[] [id, meta]
	 */
	public function fromStaticRuntimeId(int $runtimeId) : array {
		$v = $this->runtimeToLegacyMap[$runtimeId];
		return [$v >> 4, $v & 0xf];
	}

	/**
	 * @return CompoundTag[]
	 */
	public function getBedrockKnownStates() : array {
		return $this->bedrockKnownStates;
	}

	/**
	 * @return string
	 */
	public function getBedrockKnownStatesRaw() : string {
		return $this->bedrockKnownStatesRaw;
	}

	private function registerMapping(int $staticRuntimeId, int $legacyId, int $legacyMeta) : void {
		$this->legacyToRuntimeMap[($legacyId << 4) | $legacyMeta] = $staticRuntimeId;
		$this->runtimeToLegacyMap[$staticRuntimeId] = ($legacyId << 4) | $legacyMeta;
	}

}