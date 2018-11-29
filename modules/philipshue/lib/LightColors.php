<?php namespace AlphaHue;

trait LightColors
{
    /**
     * Get XY from Red, Blue, Green value.
     *
     * @param int $color Color value, number between 0 and 255.
     *
     * @return number 
     */
    public function convertColorToPoint($color)
    {
        $color = $color < 0   ? 0   : $color;
        $color = $color > 255 ? 255 : $color;
        return ($color > 0.04045) ? pow(($color + 1.055), 2.4) : ($color / 12.92);
    }

    /**
     * Converts Hex to RGB.
     * 
     * @param string $hex Hex string.
     *
     * @return array Array of color values.
     */
    public function hexToRGB($hex)
    {
        $hex = ltrim($hex, '#');

        list($rgb['red'], $rgb['green'], $rgb['blue']) = str_split($hex, 2);

        $rgb = array_map('hexdec', $rgb);

        return $rgb;
    }

    /**
     * Get XY Point from Hex.
     *
     * @param string $hex Hex string.
     *
     * @return array XY point.
     */
    public function getXYPointFromHex($hex)
    {
        $rgb = $this->hexToRGB($hex);
        return $this->getXYPointFromRGB($rgb);
    }

    /**
     * Get XY Point from RGB.
     *
     * @param int $red   Integer between 0 and 255.
     * @param int $green Integer between 0 and 255.
     * @param int $blue  Integer between 0 and 255.
     *
     * @return array Array of xy coordinates.
     */
    public function getXYPointFromRGB($rgb)
    {

        $rgb['red'] = $this->convertColorToPoint($rgb['red']);
        $rgb['green'] = $this->convertColorToPoint($rgb['green']);
        $rgb['blue'] = $this->convertColorToPoint($rgb['blue']);

        $x = $rgb['red'] * 0.4360747 + $rgb['green'] * 0.3850649 + $rgb['blue'] * 0.0930804;
        $y = $rgb['red'] * 0.2225045 + $rgb['green'] * 0.7168786 + $rgb['blue'] * 0.0406169;
        $z = $rgb['red'] * 0.0139322 + $rgb['green'] * 0.0971045 + $rgb['blue'] * 0.7141733;

        if (0 == ($x + $y + $z)) {
            $cx = $cy = 0;
        } else {
            $cx = $x / ($x + $y + $z);
            $cy = $y / ($x + $y + $z);
        }

        return array($cx, $cy);
    }


    public  function xyToRGB($x,$y,$bri){
        // Calculate XYZ values
        $z = 1 - $x - $y;
        $Y = $bri / 254; // Brightness coeff.
        if ($y == 0){
            $X = 0;
            $Z = 0;
        } else {
            $X = ($Y / $y) * $x;
            $Z = ($Y / $y) * $z;
        }
        // Convert to sRGB D65 (official formula on meethue)
        // old formula
        // $r = $X * 3.2406 - $Y * 1.5372 - $Z * 0.4986;
        // $g = - $X * 0.9689 + $Y * 1.8758 + $Z * 0.0415;
        // $b = $X * 0.0557 - $Y * 0.204 + $Z * 1.057;
        // formula 2016
        $r =   $X * 1.656492 - $Y * 0.354851 - $Z * 0.255038;
        $g = - $X * 0.707196 + $Y * 1.655397 + $Z * 0.036152;
        $b =   $X * 0.051713 - $Y * 0.121364 + $Z * 1.011530;
        // Apply reverse gamma correction
        $r = ($r <= 0.0031308 ? 12.92 * $r : (1.055) * pow($r, (1 / 2.4)) - 0.055);
        $g = ($g <= 0.0031308 ? 12.92 * $g : (1.055) * pow($g, (1 / 2.4)) - 0.055);
        $b = ($b <= 0.0031308 ? 12.92 * $b : (1.055) * pow($b, (1 / 2.4)) - 0.055);
        // Calculate final RGB
        $r = ($r < 0 ? 0 : round($r * 255));
        $g = ($g < 0 ? 0 : round($g * 255));
        $b = ($b < 0 ? 0 : round($b * 255));
        $r = ($r > 255 ? 255 : $r);
        $g = ($g > 255 ? 255 : $g);
        $b = ($b > 255 ? 255 : $b);
        // Create a web RGB string (format #xxxxxx)
        $RGB = "#".substr("0".dechex($r),-2).substr("0".dechex($g),-2).substr("0".dechex($b),-2);
        return $RGB;
    }

}
