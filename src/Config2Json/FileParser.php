<?php

namespace Config2Json;

class FileParser extends RapifiedArmaClass
{
	public $parsed_data;

	public function __construct()
	{

	}

	public function parse($file_path)
	{
		/*
		https://community.bistudio.com/wiki/raP_File_Format_-_Elite

		struct raP
		{
			char   Signature[4];               // 4 byte raP signature (\0raP)
			byte   AuthenticationSignature[20] // XBOX ONLY NOT ARMA
			ulong  Always0;
			ulong  Always8;
			ulong  OffsetToEnums;  

			ClassBody  ClassBody;              // one and one only, within which, will be more class bodies

			Enums
			{
				ulong  nEnums;  // generally always 0
				enumlist....    // optional
			};
		};
		*/

		$this->file = fopen($file_path, 'r');

		if ($this->readBytes() == 0x0) {
			$tag = $this->readBytes(3);

			if ($tag != "raP") {
				return false;
			}

			// Skip to body.
			$always_0 = $this->readInt32();
			$always_8 = $this->readInt32();
			$offset_to_enums = $this->readInt32();

			$this->position = ftell($this->file);
			$this->parsed_data = $this->read();

			if (!empty($this->parsed_data)) {
				return json_encode($this->cleanData());
			}
		}
	}

	private function cleanData()
	{
		// Find profile variables and grab all items.
		if (array_key_exists('data', $this->parsed_data)) {
			if (array_key_exists('ProfileVariables', $this->parsed_data['data'])) {
				$items = $this->getItems($this->parsed_data['data']['ProfileVariables']);
			}
		}

		return $this->flattenItems($items);
	}

	private function getItems($data)
	{
		$items = [];

		if (array_key_exists('items', $data)) {
			$count = $data['items'];

			for ($i = 0; $i < $count; $i++) {
				$items[] = $data['Item'.$i];
			}

			return $items;
		}
		return false;
	}

	private function flattenItems($items)
	{
		$new_items = [];

		if (count($items)) {
			foreach ($items as $key => $item) {
				$value = '';

				if (array_key_exists('name', $item)) {
					$key = $item['name'];
				}

				if (array_key_exists('data', $item)) {
					if (array_key_exists('value', $item['data'])) {
						$type = $item['data']['type']['type'][0];

						switch($type) {
							case 'ARRAY':
								$new_items[$key] = $this->flattenItems($this->getItems($item['data']['value']));
								break;
							
							case 'STRING':
								$new_items[$key] = $item['data']['value'];
								break;

							case 'SCALAR':
								$new_items[$key] = $item['data']['value'][1];
								break;
						}

					} else {
						$new_items[$key] = '';
					}
				}
			}
		}

		return $new_items;
	}
}
