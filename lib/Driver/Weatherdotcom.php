<?php
/**
 * TimeObjects driver for exposing weatherdotcom data via the listTimeObjects API
 *
 * @TODO: Inject any config items needed (proxy, partner ids etc...) instead of globaling
 *        the $conf array.
 *
 *        Use Horde_Controller, Routes etc... for endpoints?
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package TimeObjects
 */
class TimeObjects_Driver_Weatherdotcom extends TimeObjects_Driver
{
    protected $_params = array('units' => 'standard',
                               'days' => 5);

    public function __construct($params)
    {
        if (empty($params['location'])) {
            // Try to get a good location string from Turba's "own" contact
            $contact = $GLOBALS['registry']->contacts->ownContact();
            $params['location'] = !empty($contact['homeCity'])
                ? $contact['homeCity']
                    . (!empty($contact['homeProvince']) ? ', ' . $contact['homeProvince'] : '')
                    . (!empty($contact['homeCountry']) ? ', ' . $contact['homeCountry'] : '')
                : $contact['workCity']
                    . (!empty($contact['workProvince']) ? ', ' . $contact['workProvince'] : '')
                    . (!empty($contact['workCountry']) ? ', ' . $contact['workCountry'] : '');
        }

        // TODO: Try some other way, maybe a hook or a new preference in Horde
        //       to set your current location, maybe with a google map?

        parent::__construct($params);
    }

    /**
     * Ensures that we meet all requirements to use this time object
     *
     * @return boolean
     */
    public function ensure()
    {
        if (!class_exists('Services_Weather') ||
            !class_exists('Cache') ||
            empty($this->_params['location']) ||
            empty($GLOBALS['conf']['weatherdotcom']['partner_id']) ||
            empty($GLOBALS['conf']['weatherdotcom']['license_key'])) {

            return false;
        }

        return true;
    }

    /**
     *
     * @param mixed $start  The start time of the period
     * @param mixed $time   The end time of the period
     *
     * @return array of listTimeObjects arrays.
     */
    public function listTimeObjects($start = null, $time = null)
    {
        global $conf;

        if (!class_exists('Services_Weather') || !class_exists('Cache')) {
            throw new TimeObjects_Exception('Services_Weather or PEAR Cache Classes not found.');
        }

        $options = array();
        if (!empty($conf['http']['proxy']['proxy_host'])) {
            $proxy = 'http://';
            if (!empty($conf['http']['proxy']['proxy_user'])) {
                $proxy .= urlencode($conf['http']['proxy']['proxy_user']);
                if (!empty($conf['http']['proxy']['proxy_pass'])) {
                    $proxy .= ':' . urlencode($conf['http']['proxy']['proxy_pass']);
                }
                $proxy .= '@';
            }
            $proxy .= $conf['http']['proxy']['proxy_host'];
            if (!empty($conf['http']['proxy']['proxy_port'])) {
                $proxy .= ':' . $conf['http']['proxy']['proxy_port'];
            }

            $options['httpProxy'] = $proxy;
        }

        if (empty($this->_params['location'])) {
            throw new TimeObjects_Exception(_("No location is set."));
        }

        $weatherDotCom = &Services_Weather::service('WeatherDotCom', $options);
        $weatherDotCom->setAccountData(
            (isset($conf['weatherdotcom']['partner_id']) ? $conf['weatherdotcom']['partner_id'] : ''),
            (isset($conf['weatherdotcom']['license_key']) ? $conf['weatherdotcom']['license_key'] : ''));

        $cacheDir = Horde::getTempDir();
        if (!$cacheDir) {
            throw new TimeObjects_Exception(_("No temporary directory available for cache."));
        } else {
            $weatherDotCom->setCache('file', array('cache_dir' => ($cacheDir . '/')));
        }
        $weatherDotCom->setDateTimeFormat('m.d.Y', 'H:i');
        $weatherDotCom->setUnitsFormat($this->_params['units']);
        $units = $weatherDotCom->getUnitsFormat();

        // If the user entered a zip code for the location, no need to
        // search (weather.com accepts zip codes as location IDs).
        $search = (preg_match('/\b(?:\\d{5}(-\\d{5})?)|(?:[A-Z]{4}\\d{4})\b/',
            $this->_params['location'], $matches) ?
            $matches[0] :
            $weatherDotCom->searchLocation($this->_params['location']));
        if (is_a($search, 'PEAR_Error')) {
            switch ($search->getCode()) {
            case SERVICES_WEATHER_ERROR_SERVICE_NOT_FOUND:
                throw new TimeObjects_Exception(_("Requested service could not be found."));
            case SERVICES_WEATHER_ERROR_UNKNOWN_LOCATION:
                throw new TimeObjects_Exception(_("Unknown location provided."));
            case SERVICES_WEATHER_ERROR_WRONG_SERVER_DATA:
                throw new TimeObjects_Exception(_("Server data wrong or not available."));
            case SERVICES_WEATHER_ERROR_CACHE_INIT_FAILED:
                throw new TimeObjects_Exception(_("Cache init was not completed."));
            case SERVICES_WEATHER_ERROR_DB_NOT_CONNECTED:
                throw new TimeObjects_Exception(_("MetarDB is not connected."));
            case SERVICES_WEATHER_ERROR_UNKNOWN_ERROR:
                throw new TimeObjects_Exception(_("An unknown error has occured."));
            case SERVICES_WEATHER_ERROR_NO_LOCATION:
                throw new TimeObjects_Exception(_("No location provided."));
            case SERVICES_WEATHER_ERROR_INVALID_LOCATION:
                throw new TimeObjects_Exception(_("Invalid location provided."));
            case SERVICES_WEATHER_ERROR_INVALID_PARTNER_ID:
                throw new TimeObjects_Exception(_("Invalid partner id."));
            case SERVICES_WEATHER_ERROR_INVALID_PRODUCT_CODE:
                throw new TimeObjects_Exception(_("Invalid product code."));
            case SERVICES_WEATHER_ERROR_INVALID_LICENSE_KEY:
               throw new TimeObjects_Exception(_("Invalid license key."));
            default:
                throw new TimeObjects_Exception($search->getMessage());
            }
        }
        if (is_array($search)) {
            $search = key($search);
        }
        $forecast = $weatherDotCom->getForecast($search, $this->_params['days']);
        if (is_a($forecast, 'PEAR_Error')) {
            throw new TimeObjects_Exception($forecast->getMessage());
        }

        $now = new Horde_Date(time());
        $objects = array();
        foreach ($forecast['days'] as $which => $data) {
            $day = new Horde_Date($now);
            $day->mday += $which;
            $day_end = new Horde_Date($day);
            $day_end->mday++;

            // For day 0, the day portion isn't available after a certain time
            // simplify and just check for it's presence or use night.
            $title = sprintf("%s %d%s/%d%s", (!empty($data['day']['condition']) ? $data['day']['condition'] : $data['night']['condition']),
                                             $data['temperatureHigh'],
                                             String::upper($units['temp']),
                                             $data['temperatureLow'],
                                             String::upper($units['temp']));
            $objects[] = array('id' => $day->timestamp(), //???
                               'title' => $title,
                               'start' => sprintf('%d-%02d-%02dT00:00:00',
                                                  $day->year,
                                                  $day->month,
                                                  $day->mday),
                               'end' => sprintf('%d-%02d-%02dT00:00:00',
                                                $day_end->year,
                                                $day_end->month,
                                                $day_end->mday),
                                'recurrence' => Horde_Date_Recurrence::RECUR_NONE,
                                'params' => array(),
                                'icon' =>  Horde::url($GLOBALS['registry']->getImageDir('horde') . '/block/weatherdotcom/23x23/' . ($data['day']['conditionIcon'] == '-' ? 'na' : $data['day']['conditionIcon']) . '.png', true, false)
                        );
       }

        return $objects;
    }

}