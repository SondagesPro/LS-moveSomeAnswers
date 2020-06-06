<?php

/**
 * Add an attribute for some question, to always move some answer or sub question at end
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2016-2020 Denis Chenu <http://www.sondages.pro>
 * @license GPL
 * @version 1.0.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

class moveSomeAnswers extends PluginBase
{
    protected $storage = 'DbStorage';

    protected static $name = 'moveSomeAnswers';
    protected static $description = 'Allow to move some answers directly at end if random is set';

    /**
     * The settings for this plugin
     */
    protected $settings = array(
        "moveSomeAnswers" => array(
            "type " => 'string',
            'label' => 'This code is moved at end if question have random order (Single choice radio and multiple choice)',
            'default' => 'DNK',
        ),
    );

    /** @inheritdoc */
    public function init()
    {
        $this->subscribe('beforeActivate');

        $this->subscribe('beforeQuestionRender', 'moveSomeAnswersInList');
        $this->subscribe('newQuestionAttributes');

        $this->subscribe('beforeSurveySettings');
        $this->subscribe('newSurveySettings');

        /* To add own translation message source */
        $this->subscribe('afterPluginLoad');
    }

    public function newSurveySettings()
    {
        $event = $this->event;
        foreach ($event->get('settings') as $name => $value) {
            /* In order use survey setting, if not set, use global, if not set use default */
            $default = $event->get($name, null, null, isset($this->settings[$name]['default']) ? $this->settings[$name]['default'] : null);
            $this->set($name, $value, 'Survey', $event->get('survey'), $default);
        }
    }

    public function beforeSurveySettings()
    {
        $oEvent = $this->event;
        $newSettings = array();
        $moveSomeAnswersDefault = $this->get('moveSomeAnswers', null, null, $this->settings['moveSomeAnswers']['default']);
        $oEvent->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => array(
                'moveSomeAnswers' => array(
                    "type" => 'string',
                    'htmlOptions' => array(
                        'class' => 'form-control'
                    ),
                    'label' => $this->translate('This code is moved at end if question have random order'),
                    'help' => sprintf($this->translate('If you not set here, use : <code>%s</code>'), $moveSomeAnswersDefault),
                    'current' => $this->get('moveSomeAnswers', 'Survey', $oEvent->get('survey'), "")
                ),
            )
        ));
    }
    /**
     * Activate or not
     */
    public function beforeActivate()
    {
        $oToolsSmartDomDocument = Plugin::model()->find("name=:name", array(":name" => 'toolsDomDocument'));
        if (!$oToolsSmartDomDocument) {
            $this->getEvent()->set('message', gT("You must download toolsSmartDomDocument plugin"));
            $this->getEvent()->set('success', false);
        } elseif (!$oToolsSmartDomDocument->active) {
            $this->getEvent()->set('message', gT("You must activate toolsSmartDomDocument plugin"));
            $this->getEvent()->set('success', false);
        }
    }

    /**
     * Add the new attribute where it can be used, and where we already do the action.
     */
    public function newQuestionAttributes()
    {
        $event = $this->getEvent();
        $questionAttributes = array(
            'moveSomeAnswers' => array(
                "types" => "LMPQK",
                'category' => gT('Display'),
                'sortorder' => 101,
                'inputtype' => 'text',
                'default' => '',
                "help" => $this->translate('List of code separated by , or ;. If result is empty or random is not set: no change was done. Adding dot (.) deactivate default.'),
                "caption" => $this->translate('Move this code at end (separate by ,)')
            ),
        );
        $event->append('questionAttributes', $questionAttributes);
    }

    /**
     * Using beforeRenderQuestion event to move some lines at end depending on attribue
     */
    public function moveSomeAnswersInList()
    {
        $oEvent = $this->getEvent();
        if (in_array($oEvent->get('type'), array("L","M","P","Q","K"))) {
            $surveySettings = $this->get('moveSomeAnswers', 'Survey', $oEvent->get('surveyId'));
            if ($surveySettings == "") {
                $surveySettings = $this->get('moveSomeAnswers', null, null, $this->settings['moveSomeAnswers']['default']);
            }
            $aAttributes = QuestionAttribute::model()->getQuestionAttributes($this->getEvent()->get('qid'));
            if ($aAttributes["random_order"] || $surveySettings) {
                /* @todo : must use EM for $aAttributes["orderByAnswers"] */
                $moveSomeAnswers = trim($aAttributes["moveSomeAnswers"]);
                if (empty($moveSomeAnswers)) {
                    $moveSomeAnswers = $surveySettings;
                }
                if ($moveSomeAnswers == "") {
                    $moveSomeAnswers = $this->get('moveSomeAnswers', 'Survey', $oEvent->get('surveyId'));
                    if ($moveSomeAnswers == "") {
                        $moveSomeAnswers = $this->get('moveSomeAnswers', null, null, $this->settings['moveSomeAnswers']['default']);
                    }
                }
                if ($moveSomeAnswers !== "" && $moveSomeAnswers !== ".") {
                    $aAtEnd = explode(",", $moveSomeAnswers);
                    $dom = new \toolsDomDocument\SmartDOMDocument();
                    $dom->loadPartialHTML($this->event->get('answers'));
                    $bUpdated = false;
                    switch ($oEvent->get('type')) {
                        case "L":
                        case "Q":
                        case "K":
                            $lineBaseId = "javatbd{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";
                            foreach ($aAtEnd as $sAtEnd) {
                                // @todo : Control LS version
                                $line = $dom->getElementById($lineBaseId . $sAtEnd);
                                if ($line) {
                                    $parentList = $line->parentNode;
                                    $parentList->removeChild($line);
                                    $parentList->appendChild($line);
                                    $bUpdated = true;
                                }
                            }
                            /* Optionaly move no answer to end : wait for clearing HTML code */
                            break;
                        case "M":
                        case "P":
                            $lineBaseId = "javatbd{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";
                            foreach ($aAtEnd as $sAtEnd) {
                                $line = $dom->getElementById($lineBaseId . $sAtEnd);
                                if ($line) {
                                    $parentList = $line->parentNode;
                                    $parentList->removeChild($line);
                                    $parentList->appendChild($line);
                                    $bUpdated = true;
                                }
                            }
                            // no break
                        default:
                            break;
                    }
                    if ($bUpdated) {
                        /* For multiple numeric : must move some part at end */
                        if ($oEvent->get('type') == "K" && $parentList) {
                            $childToMove = array();
                            foreach ($parentList->getElementsByTagName('tr') as $child) {
                                if (!$child->hasAttribute('id')) {
                                    $childToMove[] = $child;
                                }
                            }
                            foreach ($childToMove as $child) {
                                $parentList->removeChild($child);
                                $parentList->appendChild($child);
                            }
                        }
                        $newHtml = $dom->saveHTMLExact();
                        $oEvent->set('answers', $newHtml);
                    }
                }
            }
        }
    }

    private function translate($string)
    {
        return Yii::t('', $string, array(), get_class($this));
    }
    /**
     * Add this translation just after loaded all plugins
     * @see event afterPluginLoad
     */
    public function afterPluginLoad()
    {
        // messageSource for this plugin:
        $messageMaintenanceMode = array(
            'class' => 'CGettextMessageSource',
            'cacheID' => get_class($this) . 'Lang',
            'cachingDuration' => 3600,
            'forceTranslation' => true,
            'useMoFile' => true,
            'basePath' => __DIR__ . DIRECTORY_SEPARATOR . 'locale',
            'catalog' => 'messages',// default from Yii
        );
        Yii::app()->setComponent(get_class($this), $messageMaintenanceMode);
    }
}
