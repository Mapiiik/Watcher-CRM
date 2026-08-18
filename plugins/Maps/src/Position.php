<?php
declare(strict_types=1);

namespace Maps;

/**
 * A point on the map.
 */
class Position
{
    /**
     * Mean radius of the earth, in metres.
     */
    private const EARTH_RADIUS = 6371000.0;

    /**
     * Latitude
     */
    public float $lat;

    /**
     * Longitude
     */
    public float $lng;

    /**
     * Constructor
     */
    public function __construct(float $lat, float $lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    /**
     * Return values as array(lat/lng)
     *
     * @return array<string, float>
     */
    public function toArray(): array
    {
        return ['lat' => $this->lat, 'lng' => $this->lng];
    }

    /**
     * Distance to another point, in metres, along the surface of the earth.
     *
     * @param \Maps\Position $other The point to measure to
     * @return float
     */
    public function distanceTo(Position $other): float
    {
        $latitude = deg2rad($other->lat - $this->lat);
        $longitude = deg2rad($other->lng - $this->lng);

        $a = sin($latitude / 2) ** 2
            + cos(deg2rad($this->lat)) * cos(deg2rad($other->lat)) * sin($longitude / 2) ** 2;

        return self::EARTH_RADIUS * 2 * asin(min(1.0, sqrt($a)));
    }
}
