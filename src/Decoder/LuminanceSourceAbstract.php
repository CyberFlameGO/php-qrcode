<?php
/**
 * Class LuminanceSourceAbstract
 *
 * @created      24.01.2021
 * @author       ZXing Authors
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2021 Smiley
 * @license      Apache-2.0
 */

namespace chillerlan\QRCode\Decoder;

use function array_slice, array_splice, file_exists, is_file, is_readable, realpath;

/**
 * The purpose of this class hierarchy is to abstract different bitmap implementations across
 * platforms into a standard interface for requesting greyscale luminance values. The interface
 * only provides immutable methods; therefore crop and rotation create copies. This is to ensure
 * that one Reader does not modify the original luminance source and leave it in an unknown state
 * for other Readers in the chain.
 *
 * @author dswitkin@google.com (Daniel Switkin)
 */
abstract class LuminanceSourceAbstract implements LuminanceSourceInterface{

	protected array $luminances;
	protected int   $width;
	protected int   $height;

	/**
	 *
	 */
	public function __construct(int $width, int $height){
		$this->width  = $width;
		$this->height = $height;
		// In order to measure pure decoding speed, we convert the entire image to a greyscale array
		// up front, which is the same as the Y channel of the YUVLuminanceSource in the real app.
		$this->luminances = [];
	}

	/** @inheritDoc */
	public function getMatrix():array{
		return $this->luminances;
	}

	/** @inheritDoc */
	public function getWidth():int{
		return $this->width;
	}

	/** @inheritDoc */
	public function getHeight():int{
		return $this->height;
	}

	/** @inheritDoc */
	public function getRow(int $y):array{

		if($y < 0 || $y >= $this->getHeight()){
			throw new QRCodeDecoderException('Requested row is outside the image: '.$y);
		}

		$arr = [];

		array_splice($arr, 0, $this->width, array_slice($this->luminances, $y * $this->width, $this->width));

		return $arr;
	}

	/**
	 *
	 */
	protected function setLuminancePixel(int $r, int $g, int $b):void{
		$this->luminances[] = $r === $g && $g === $b
			// Image is already greyscale, so pick any channel.
			? $r // (($r + 128) % 256) - 128;
			// Calculate luminance cheaply, favoring green.
			: ($r + 2 * $g + $b) / 4; // (((($r + 2 * $g + $b) / 4) + 128) % 256) - 128;
	}

	/**
	 * @throws \chillerlan\QRCode\Decoder\QRCodeDecoderException
	 */
	protected static function checkFile(string $path):string{
		$path = trim($path);

		if(!file_exists($path) || !is_file($path) || !is_readable($path)){
			throw new QRCodeDecoderException('invalid file: '.$path);
		}

		$realpath = realpath($path);

		if($realpath === false){
			throw new QRCodeDecoderException('unable to resolve path: '.$path);
		}

		return $realpath;
	}

}