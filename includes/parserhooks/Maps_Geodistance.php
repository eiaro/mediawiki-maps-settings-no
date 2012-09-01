<?php

/**
 * Class for the 'geodistance' parser hooks, which can
 * calculate the geographical distance between two points.
 * 
 * @since 0.7
 * 
 * @file Maps_Geodistance.php
 * @ingroup Maps
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapsGeodistance extends ParserHook {
	/**
	 * No LSB in pre-5.3 PHP *sigh*.
	 * This is to be refactored as soon as php >=5.3 becomes acceptable.
	 */	
	public static function staticInit( Parser &$parser ) {
		$instance = new self;
		return $instance->init( $parser );
	}	
	
	/**
	 * Gets the name of the parser hook.
	 * @see ParserHook::getName
	 * 
	 * @since 0.7
	 * 
	 * @return string
	 */
	protected function getName() {
		return 'geodistance';
	}
	
	/**
	 * Returns an array containing the parameter info.
	 * @see ParserHook::getParameterInfo
	 * 
	 * @since 0.7
	 * 
	 * @return array
	 */
	protected function getParameterInfo( $type ) {
		global $egMapsDistanceUnit, $egMapsDistanceDecimals, $egMapsAvailableGeoServices, $egMapsDefaultGeoService; 
		
		$params = array();

		$params['mappingservice'] = array(
			'default' => '',
			'values' => MapsMappingServices::getAllServiceValues(),
			// new ParamManipulationFunctions( 'strtolower' ) FIXME
		);

		$params['geoservice'] = array(
			'default' => $egMapsDefaultGeoService,
			'aliases' => 'service',
			'values' => $egMapsAvailableGeoServices,
			// new ParamManipulationFunctions( 'strtolower' ) FIXME
		);

		$params['unit'] = array(
			'default' => $egMapsDistanceUnit,
			'values' => MapsDistanceParser::getUnits(),
		);

		$params['decimals'] = array(
			'type' => 'integer',
			'default' => $egMapsDistanceDecimals,
		);

		$params['location1'] = array(
			'aliases' => 'from',
			'dependencies' => array( 'mappingservice', 'geoservice' ),
			// FIXME new CriterionIsLocation()
		);

		$params['location2'] = array(
			'aliases' => 'to',
			'dependencies' => array( 'mappingservice', 'geoservice' ),
			// FIXME new CriterionIsLocation()
		);

		foreach ( $params as $name => &$param ) {
			$param['message'] = 'maps-geodistance-par-' . $name;
		}

		return $params;
	}
	
	/**
	 * Returns the list of default parameters.
	 * @see ParserHook::getDefaultParameters
	 * 
	 * @since 0.7
	 * 
	 * @return array
	 */
	protected function getDefaultParameters( $type ) {
		return array( 'location1', 'location2', 'unit', 'decimals' );
	}
	
	/**
	 * Renders and returns the output.
	 * @see ParserHook::render
	 * 
	 * @since 0.7
	 * 
	 * @param array $parameters
	 * 
	 * @return string
	 */
	public function render( array $parameters ) {
		if ( MapsGeocoders::canGeocode() ) {
			$start = MapsGeocoders::attemptToGeocode( $parameters['location1'], $parameters['geoservice'], $parameters['mappingservice'] );
			$end = MapsGeocoders::attemptToGeocode( $parameters['location2'], $parameters['geoservice'], $parameters['mappingservice'] );
		} else {
			$start = MapsCoordinateParser::parseCoordinates( $parameters['location1'] );
			$end = MapsCoordinateParser::parseCoordinates( $parameters['location2'] );
		}
		
		if ( $start && $end ) {
			$output = MapsDistanceParser::formatDistance( MapsGeoFunctions::calculateDistance( $start, $end ), $parameters['unit'], $parameters['decimals'] );
		} else {
			// The locations should be valid when this method gets called.
			throw new MWException( 'Attempt to find the distance between locations of at least one is invalid' );
		}

		return $output;
	}

	/**
	 * @see ParserHook::getMessage()
	 * 
	 * @since 1.0
	 */
	public function getMessage() {
		return 'maps-geodistance-description';
	}	
	
}