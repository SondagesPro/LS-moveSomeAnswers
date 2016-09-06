<?php
/**
 * Add an attribute for some question, to always move some answer or sub question at end
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2016 Denis Chenu <http://www.sondages.pro>
 * @license GPL
 * @version 0.1.1
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

    static protected $name = 'moveSomeAnswers';
    static protected $description = 'Allow to move some answers directly at end if random is set';

  /**
   * The settings for this plugin
   */
    protected $settings=array(
        "moveSomeAnswers"=>array(
            "type"=>'string',
            'label'=>'This code is moved at end if question have random order (Single choice radio and multiple choice)',
            'default'=>'DNK',
        ),
    );



    public function init()
    {
        $this->subscribe('beforeActivate');

        $this->subscribe('beforeQuestionRender','moveSomeAnswersInList');
        $this->subscribe('newQuestionAttributes');

        $this->subscribe('beforeSurveySettings');
        $this->subscribe('newSurveySettings');

    }

    public function beforeSurveySettings()
    {
        $oEvent = $this->event;
        $newSettings=array();
        $moveSomeAnswersDefault=$this->get('moveSomeAnswers',null,null,$this->settings['moveSomeAnswers']['default']);
        $oEvent->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => array(
                'moveSomeAnswers'=>array(
                    "type"=>'string',
                    'htmlOptions'=>array(
                      'class'=>'form-control'
                    ),
                    'label'=>'This code is moved at end if question have random order ',
                    'help'=>'If you not set here, use : <code>'.$moveSomeAnswersDefault.'</code>',
                    'current'=>$this->get('moveSomeAnswers','Survey',$oEvent->get('survey'),"")
                ),
            )
        ));
    }
    /**
     * Activate or not
     */
    public function beforeActivate()
    {
        $oToolsSmartDomDocument = Plugin::model()->find("name=:name",array(":name"=>'toolsDomDocument'));
        if(!$oToolsSmartDomDocument)
        {
            $this->getEvent()->set('message', gT("You must download toolsSmartDomDocument plugin"));
            $this->getEvent()->set('success', false);
        }
        elseif(!$oToolsSmartDomDocument->active)
        {
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
            'moveSomeAnswers'=>array(
                "types"=>"LMPQK",
                'category'=>gT('Display'),
                'sortorder'=>101,
                'inputtype'=>'text',
                'default'=>'',
                "help"=>'List of code separated by , or ;. If result is empty or random is not set: no change was done. Adding dot (.) deactivate default.',
                "caption"=>'Move this code at end (separate by ,)'
            ),
        );
        $event->append('questionAttributes', $questionAttributes);
    }

    /**
     * Using beforeRenderQuestion event to move some lines at end depending on attribue
     */
    public function moveSomeAnswersInList()
    {
        $oEvent=$this->getEvent();
        if(in_array($oEvent->get('type'),array("L","M","P","Q","K")))
        {
            $aAttributes=QuestionAttribute::model()->getQuestionAttributes($this->getEvent()->get('qid'));
            if($aAttributes["random_order"])
            {
                /* @todo : must use EM for $aAttributes["orderByAnswers"] */
                $moveSomeAnswers=trim($aAttributes["moveSomeAnswers"]);
                if($moveSomeAnswers=="")
                {
                    $moveSomeAnswers=$this->get('moveSomeAnswers','Survey',$oEvent->get('surveyId'));
                    if($moveSomeAnswers=="")
                    {
                        $moveSomeAnswers=$this->get('moveSomeAnswers',null,null,$this->settings['moveSomeAnswers']['default']);
                    }
                }
                if($moveSomeAnswers!=="" && $moveSomeAnswers!==".")
                {
                    $aAtEnd=explode(",",$moveSomeAnswers);
                    $dom = new \toolsDomDocument\SmartDOMDocument();
                    $dom->loadPartialHTML($this->event->get('answers'));
                    $bUpdated=false;
                    switch ($oEvent->get('type'))
                    {
                        case "L":
                        case "Q":
                        case "K":// 2.51.1 : in an array ....
                            $lineBaseId="javatbd{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";
                            foreach($aAtEnd as $sAtEnd)
                            {
                                // @todo : Control LS version
                                $line=$dom->getElementById($lineBaseId.$sAtEnd);
                                if($line)
                                {
                                    $parentList=$line->parentNode;
                                    $parentList->removeChild($line);
                                    $parentList->appendChild($line);
                                    $bUpdated=true;
                                }
                            }
                            /* Optionaly move no answer to end : wait for clearing HTML code */
                            break;
                        case "M":
                        case "P":
                            $lineBaseId="javatbd{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";
                            foreach($aAtEnd as $sAtEnd)
                            {
                                $line=$dom->getElementById($lineBaseId.$sAtEnd);
                                if($line)
                                {
                                    // @todo : Control LS version
                                    $element=$line->parentNode;// Arg ..... LS 2.50.1, and after ?
                                    $parentList=$element->parentNode;
                                    $parentList->removeChild($element);
                                    $parentList->appendChild($element);
                                    $bUpdated=true;
                                }
                            }
                        default:
                            break;
                    }
                    if($bUpdated)
                    {
                        $newHtml = $dom->saveHTMLExact();
                        $oEvent->set('answers',$newHtml);
                    }
                }
            }
        }
    }
}
