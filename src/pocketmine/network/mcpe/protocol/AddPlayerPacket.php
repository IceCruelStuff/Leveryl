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

namespace pocketmine\network\mcpe\protocol;

class AddPlayerPacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::ADD_PLAYER_PACKET;

	public $uuid;
	public $username;
	public $thirdPartyName = "";
	public $platform = 0;
	public $eid;
	public $string1 = "";
	public $x;
	public $y;
	public $z;
	public $speedX;
	public $speedY;
	public $speedZ;
	public $pitch;
	public $headYaw;
	public $yaw;
	public $item;
	public $metadata = [];

	/**
	 *
	 */
	public function decode(){
		$this->uuid = $this->getUUID();
		$this->username = $this->getString();
		$this->thirdPartyName = $this->getString();
		$this->platform = $this->getVarInt();
		$this->eid = $this->getEntityId();
		$this->eid = $this->getEntityId();
		$this->string1 = $this->getString();
		$this->position = $this->getVector3();
		$this->motion = $this->getVector3();
		$this->pitch = $this->getLFloat();
		$this->headYaw = $this->getLFloat();
		$this->yaw = $this->getLFloat();
		$this->item = $this->getSlot();
		$this->metadata = $this->putEntityMetadata();
	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		$this->putUUID($this->uuid);
		$this->putString($this->username);
		$this->putString($this->thirdPartyName);
		$this->putVarInt($this->platform);
		$this->putEntityId($this->eid); //EntityUniqueID
		$this->putEntityId($this->eid); //EntityRuntimeID
		$this->putString($this->string1);
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putVector3f($this->speedX, $this->speedY, $this->speedZ);
		$this->putLFloat($this->pitch);
		$this->putLFloat($this->headYaw ?? $this->yaw);
		$this->putLFloat($this->yaw);
		$this->putSlot($this->item);
		$this->putEntityMetadata($this->metadata);
	}

	/**
	 * @return PacketName|string
	 */
	public function getName(){
		return "AddPlayerPacket";
	}

}
