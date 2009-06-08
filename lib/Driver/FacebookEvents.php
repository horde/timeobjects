<?php
/**
 * TimeObjects driver for exposing a user's Facebook Events via the
 * listTimeObjects API.
 *
 *
 */
class TimeObjects_Driver_FacebookEvents
{
    private $_fb_session;

    public function ensure()
    {
        if (!$GLOBALS['conf']['facebook']['enabled']) {
            return false;
        }

        $fbp = unserialize($GLOBALS['prefs']->getValue('facebook'));
        if (empty($fbp['uid']) || empty($fbp['sid'])) {
            return false;
        } else {
            $this->_fb_session = $fbp;
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
        $fb = $this->_getFacebook();
        $events = $fb->events->get();
        $objects = array();
        foreach ($events as $event) {
            // FB s*cks. This may be right, or it may be wrong, or they may
            // change it, who knows.
            $event['start_time'] -= 21600; //60 * 60 * 6;
            $start = new Horde_Date($event['start_time'], 'America/Los_Angeles');
            $start->setTimezone($GLOBALS['prefs']->getValue('timezone', date_default_timezone_get()));
            $event['end_time'] -= 21600;
            $end = new Horde_Date($event['end_time'], 'America/Los_Angeles');
            $end->setTimezone($GLOBALS['prefs']->getValue('timezone', date_default_timezone_get()));

            $objects[] = array('id' => $event['eid'],
                               'title' => $event['name'] . ' - ' . $event['tagline'],
                               'start' => sprintf('%d-%02d-%02dT%02d:%02d:00',
                                                  $start->year,
                                                  $start->month,
                                                  $start->mday,
                                                  $start->hour,
                                                  $start->min),
                               'end' => sprintf('%d-%02d-%02dT%02d:%02d:00',
                                                  $end->year,
                                                  $end->month,
                                                  $end->mday,
                                                  $end->hour,
                                                  $end->min),
                               'recurrence' => Horde_Date_Recurrence::RECUR_NONE,
                               'params' => array(),
                               'icon' => $event['pic_small']
                              );
        }

        return $objects;
    }

    private function _getFacebook()
    {
        global $conf;

        if (empty($this->_fb_session['uid']) || empty($this->_fb_session['sid'])) {
            if (!$this->ensure()) {
                throw new TimeObjects_Exception('Cannot load Facebook object.');
            }
        }

        $context = array('http_client' => new Horde_Http_Client(),
                         'http_request' => new Horde_Controller_Request_Http());
        $facebook = new Horde_Service_Facebook($conf['facebook']['key'],
                                               $conf['facebook']['secret'],
                                               $context);
        $facebook->auth->setUser($this->_fb_session['uid'],
                                        $this->_fb_session['sid'],
                                        0);
       return $facebook;

    }
}