<?php

namespace Config2Json;

class FileParser extends RapifiedArmaClass
{
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
			return json_encode($this->read());
		}
	}
}
