<?php

namespace Config2Json;

class RapifiedArmaClass
{
	public $file;
	public $packet_types = [
		'class_name' => 0
		, 'token' => 1
		, 'array' => 2
	];
	public $variable_types = [
		'string' => 0
		, 'float' => 1
		, 'integer' => 2
	];
	public $array_types = [
		'string' => 0
		, 'float' => 1
		, 'integer' => 2
		, 'array' => 3
		, 'variable' => 4
	];
	public $name = '';
	public $data = [];

	public function __construct($file, $name = '', $position = 0)
	{
		$this->name = $name;
		$this->position = $position;
		$this->file = $file;
	}

	public function read()
	{
		fseek($this->file, $this->position);
		$name = $this->readString();

		if ($name != '') {
			$this->name = $name;
		}
		
		$items = $this->readCompressedInt();
		$this->readItems($items);

		return [
			'name' => $this->name
			, 'data' => $this->data
		];
	}

	public function readItems($items)
	{
		$children = [];

		for ($i = 0; $i < $items; $i++) {
			$packet_type = $this->byteToUint($this->readBytes());

			switch ($packet_type) {
				case $this->packet_types['class_name']:
					$children[] = $this->childClass();
					break;

				case $this->packet_types['token']:
					$token = $this->token();
					$this->data[$token['name']] = $token['value'];
					break;

				case $this->packet_types['array']:
					$array = $this->array();
					$this->data[$array['name']] = $array['array'];
					break;

				default:
					return false;
			}
		}

		foreach ($children as $child) {
			$child_data = $child->read();
			if (array_keys($child_data)[0] != 'type') {
				$this->data[$child_data['name']] = $child_data['data'];
			}
		}
	}

	public function childClass()
	{
		$name = $this->readString();
		$offset = $this->readInt32();
		return new RapifiedArmaClass($this->file, $name, $offset);
	}

	public function token()
	{
		$var_type = $this->byteToUint($this->readBytes());
		$data['name'] = $this->readString();

		switch ($var_type) {
			case $this->variable_types['string']:
				$data['value'] = $this->readString();
				break;

			case $this->variable_types['float']:
				$data['value'] = $this->readFloat();
				break;

			case $this->variable_types['integer']:
				$data['value'] = $this->readInt32();
				break;
		}

		return $data;
	}

	public function array()
	{
		$data['name'] = $this->readString();
		$count = $this->readCompressedInt();

		for ($i = 0; $i < $count; $i++) {
			$packet_type = $this->byteToUint($this->readBytes());

			switch ($packet_type) {
				case $this->array_types['string']:
					$data['array'][] = $this->readString();
					break;

				case $this->array_types['float']:
					$data['array'][] = $this->readFloat();
					break;

				case $this->array_types['integer']:
					$data['array'][] = $this->readInt32();
					break;
			}
		}

		return $data;
	}

	public function readBytes($num_bytes = 1)
	{
		return fread($this->file, $num_bytes);
	}

	public function readString()
	{
		$string = '';
		$chr_int = $this->byteToUint($this->readBytes());

		while ($chr_int != 0) {
			$string .= chr($chr_int);
			$chr_int = $this->byteToUint($this->readBytes());
		}

		return $string;
	}

	public function readInt32()
	{
		return unpack('Lint', $this->readBytes(4))['int'];
	}

	public function readFloat()
	{
		return unpack('f', $this->readBytes(4));
	}

	public function readCompressedInt()
	{
		$val = $this->byteToUint($this->readBytes());
		$has_extra = $this->isBitSet($val, 7);

		while ($has_extra) {
			$extra = $this->byteToUint($this->readBytes());
			$val += ($extra - 1) * 0x80;

			if (!$this->isBitSet($extra, 7)) {
				break;
			}
		}

		return $val;
	}

	public function isBitSet($val, $pos)
	{
		return ($val & (1 << $pos)) != 0;
	}

	public function byteToUint($byte)
	{
		// The least verbose way I could find to convert a byte into a 32bit uint.
		return hexdec(bin2hex($byte));
	}
}
