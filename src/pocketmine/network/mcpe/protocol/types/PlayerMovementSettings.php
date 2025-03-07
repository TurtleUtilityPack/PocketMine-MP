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

namespace pocketmine\network\mcpe\protocol\types;

final class PlayerMovementSettings {

	/** @var int */
	private $movementType;

	/** @var int */
	private $rewindHistorySize;

	/** @var bool */
	private $serverAuthoritativeBlockBreaking;

	public function __construct(int $movementType, int $rewindHistorySize = 0, bool $serverAuthoritativeBlockBreaking = false) {
		$this->serverAuthoritativeBlockBreaking = $serverAuthoritativeBlockBreaking;
		$this->rewindHistorySize = $rewindHistorySize;
		$this->movementType = $movementType;
	}

	public function getMovementType() : int {
		return $this->movementType;
	}

	public function getRewindHistorySize() : int {
		return $this->rewindHistorySize;
	}

	public function isServerAuthoritativeBlockBreaking() : bool {
		return $this->serverAuthoritativeBlockBreaking;
	}

}