<?php
// This file is part of qtype_kprime for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 *
 *
 * @package     qtype_kprime
 * @author      Juergen Zimmer jzimmer1000@gmail.com
 * @copyright   eDaktik 2014 andreas.hruska@edaktik.at
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/edit_question_form.php');
require_once($CFG->dirroot . '/question/type/kprime/lib.php');
require_once($CFG->dirroot . '/question/type/kprime/question_edit_form.class.php');
require_once($CFG->dirroot . '/question/engine/bank.php');

/**
 * Kprime editing form definition.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_kprime_edit_form extends question_edit_form {
    private $numberofrows;
    private $numberofcolumns;

    /**
     * Adds question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
    protected function definition_inner($mform) {
        $kprimeconfig = get_config('qtype_kprime');
        if (isset($this->question->options->rows) && count($this->question->options->rows) > 0) {
            $this->numberofrows = count($this->question->options->rows);
        } else {
            $this->numberofrows = QTYPE_KPRIME_NUMBER_OF_OPTIONS;
        }
        if (isset($this->question->options->columns) && count($this->question->options->columns) > 0) {
            $this->numberofcolumns = count($this->question->options->columns);
        } else {
            $this->numberofcolumns = QTYPE_KPRIME_NUMBER_OF_RESPONSES;
        }

        $this->editoroptions['changeformat'] = 1;
        $mform->addElement('hidden', 'numberofrows', $this->numberofrows);
        $mform->setType('numberofrows', PARAM_INT);
        $mform->addElement('hidden', 'numberofcolumns', $this->numberofcolumns);
        $mform->setType('numberofcolumns', PARAM_INT);
		
		$mform->addElement('header', 'optionsandfeedbackheader', get_string("optionsandfeedback", 'qtype_kprime'));

			 
        // Add the response text fields.
        $responses = array();
        for ($i = 1; $i <= $this->numberofcolumns; $i++) {
            $label = '';
            if ($i == 1) {
                $label = get_string('responsetexts', 'qtype_kprime');
            }
            $mform->addElement('text', 'responsetext_' . $i, $label, array('size' => 6));
            $mform->setType('responsetext_' . $i, PARAM_TEXT);
            $mform->addRule('responsetext_' . $i, null, 'required', null, 'client');

            if ($this->numberofcolumns == 2) {
                $mform->setDefault('responsetext_' . $i, get_string('responsetext' . $i, 'qtype_kprime'));
            }
        }

        $responsetexts = array();
        if (isset($this->question->options->columns) && !empty($this->question->options->columns)) {
            foreach ($this->question->options->columns as $key => $column) {
                $responsetexts[] = format_text($column->responsetext, FORMAT_HTML);
            }
        } else { 
            $responsetexts[] = get_string('responsetext1', 'qtype_kprime');
            $responsetexts[] = get_string('responsetext2', 'qtype_kprime');
        }
/*        
        // Add an option text editor, response radio buttons and a feedback editor for each option.
        for ($i = 1; $i <= $this->numberofrows; $i++) {
			$mform->addElement('html', '<h5>'.get_string('optionno', 'qtype_kprime', $i).'</h5>');
			$mform->addElement('html', '<div class="well">');
			

            $mform->addElement('editor', 'option_' . $i,  get_string('optionno', 'qtype_kprime', $i) , array('rows' => 3), $this->editoroptions);
            $mform->setDefault('option_' . $i, array('text' => get_string('enteroptionhere', 'qtype_kprime')));
            $mform->setType('option_' . $i, PARAM_RAW);
            $mform->addRule('option_' . $i, null, 'required', null, 'client');



			//$mform->addElement('html', '<h5>'.get_string('feedbackforoption', 'qtype_kprime', $i).'</h5>'); //'.get_string('feedbackforoption', 'qtype_kprime', $i).'
     

            // Add the feedback text editor in a new line.

            $mform->addElement('editor', 'feedback_' . $i, get_string('feedbackforoption', 'qtype_kprime', $i), array('rows' => 3), $this->editoroptions);
            $mform->setType('feedback_' . $i, PARAM_RAW);
			//$mform->addElement('html', '</div></div>');
			
			 // Add the radio buttons for responses.
            $attributes = array();
            $radiobuttons = array();
            for ($j = 1; $j <= $this->numberofcolumns; $j++) {
                if (array_key_exists($j - 1, $responsetexts)) {
                    $radiobuttons[] =& $mform->createElement('radio', 'weightbutton_' . $i, '', $responsetexts[$j - 1], $j,
                            $attributes);
                } else {
                    $radiobuttons[] =& $mform->createElement('radio', 'weightbutton_' . $i, '', '', $j, $attributes);
                }
            }
			
            $mform->addGroup($radiobuttons, 'weightsarray_' . $i, '', array('<br />'), false);
            $mform->setDefault('weightbutton_' . $i, 1);
			$mform->addElement('html', '</div>');
			
			

        }
*/
        // Add an option text editor, response radio buttons and a feedback editor for each option.
        for ($i = 1; $i <= $this->numberofrows; $i++) {
        	// Add the option editor.
        	$mform->addElement('html', '<div class="optionbox">'); // Open div.optionbox.
        	$mform->addElement('html', '<div class="optionandresponses">'); // Open div.optionbox.
        
        	$mform->addElement('html', '<div class="optiontext">'); // Open div.optiontext.
        	$mform->addElement('html', '<label class="optiontitle">' . get_string('optionno', 'qtype_kprime', $i) .
        			'</label>');
        	$mform->addElement('editor', 'option_' . $i, '' , array('rows' => 8), $this->editoroptions);
        	$mform->setDefault('option_' . $i, array('text' => get_string('enteroptionhere', 'qtype_kprime')));
        	$mform->setType('option_' . $i, PARAM_RAW);
        	$mform->addRule('option_' . $i, null, 'required', null, 'client');
        
        	$mform->addElement('html', '</div>'); // Close div.optiontext.
        
        	// Add the radio buttons for responses.
        	$mform->addElement('html', '<div class="responses">'); // Open div.responses.
        	$attributes = array();
        	$radiobuttons = array();
        	for ($j = 1; $j <= $this->numberofcolumns; $j++) {
        		if (array_key_exists($j - 1, $responsetexts)) {
        			$radiobuttons[] =& $mform->createElement('radio', 'weightbutton_' . $i, '', $responsetexts[$j - 1], $j,
        					$attributes);
        		} else {
        			$radiobuttons[] =& $mform->createElement('radio', 'weightbutton_' . $i, '', '', $j, $attributes);
        		}
        	}
        	$mform->addGroup($radiobuttons, 'weightsarray_' . $i, '', array('<br/>'), false);
        	$mform->setDefault('weightbutton_' . $i, 1);
        
        	$mform->addElement('html', '</div>'); // Close div.responses.
        	$mform->addElement('html', '</div>'); // Close div.optionsandresponses
        
        	$mform->addElement('html', '<br /><br />'); // Close div.optionsandresponses
        
        	// Add the feedback text editor in a new line.
        	$mform->addElement('html', '<div class="feedbacktext">'); // Open div.feedbacktext.
        	$mform->addElement('html', '<label class="feedbacktitle">' . get_string('feedbackforoption', 'qtype_kprime', $i) .
        			'</label>');
        	$mform->addElement('editor', 'feedback_' . $i, '', array('rows' => 2,'placeholder'=>'hellowww'), $this->editoroptions);
        	$mform->setType('feedback_' . $i, PARAM_RAW);
        	
        	//            $mform->setDefault('feedback_' . $i, array('text' => get_string('enterfeedbackhere', 'qtype_kprime')));
        
        	$mform->addElement('html', '</div>'); // Close div.feedbacktext.
        	$mform->addElement('html', '</div><br />'); // Close div.optionbox.
        
        }        
		$mform->addElement('header', 'scoringmethodheader',  get_string('scoringmethod', 'qtype_kprime'));
        // Add the scoring method radio buttons.
        $attributes = array();
        $scoringbuttons = array();
        $scoringbuttons[] =& $mform->createElement('radio', 'scoringmethod', '', get_string('scoringkprime', 'qtype_kprime'),
                 'kprime', $attributes);
        $scoringbuttons[] =& $mform->createElement('radio', 'scoringmethod', '', get_string('scoringkprimeonezero', 'qtype_kprime'),
                 'kprimeonezero', $attributes);
        $scoringbuttons[] =& $mform->createElement('radio', 'scoringmethod', '', get_string('scoringsubpoints', 'qtype_kprime'),
                 'subpoints', $attributes);
        $mform->addGroup($scoringbuttons, 'radiogroupscoring', get_string('scoringmethod', 'qtype_kprime'), array(' <br/> '),
                 false);
        $mform->addHelpButton('radiogroupscoring', 'scoringmethod', 'qtype_kprime');
        $mform->setDefault('scoringmethod', 'kprime');

        // Add the shuffleoptions checkbox.
        $mform->addElement('advcheckbox', 'shuffleoptions',
                get_string('shuffleoptions', 'qtype_kprime'), null, null, array(0, 1));
        $mform->addHelpButton('shuffleoptions', 'shuffleoptions', 'qtype_kprime');
		

        $this->add_hidden_fields();
		//$this->add_interactive_settings();		
	
    }

    /**
     * (non-PHPdoc)
     * @see question_edit_form::data_preprocessing()
     */
    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (isset($question->options)) {
            $question->shuffleoptions = $question->options->shuffleoptions;
            $question->scoringmethod = $question->options->scoringmethod;
            $question->rows = $question->options->rows;
            $question->columns = $question->options->columns;
            $question->numberofrows = count($question->rows);
            $question->numberofcolumns = count($question->columns);
        }

        if (isset($this->question->id)) {
	        $key = 1;
        	foreach ($question->options->rows as $row) {
	        //	$question->subanswers[$key] = $row->optiontext;

        		// Restore all images in the option text.
        		$draftid = file_get_submitted_draft_itemid('option_' . $key);
        		$question->{'option_' . $key}['text'] = file_prepare_draft_area($draftid,
        				$this->context->id, 'qtype_kprime', 'optiontext',
        				!empty($row->id) ? (int) $row->id : null,
        				$this->fileoptions, $row->optiontext);
        		$question->{'option_' . $key}['itemid'] = $draftid;
		
            	// 	Now do the same for the feedback text.
    	   		$draftid = file_get_submitted_draft_itemid('feedback_' . $key);
        		$question->{'feedback_' . $key}['text'] = file_prepare_draft_area($draftid,
        				$this->context->id, 'qtype_kprime', 'feedbacktext',
        				!empty($row->id) ? (int) $row->id : null,
        				$this->fileoptions, $row->optionfeedback);
        		$question->{'feedback_' . $key}['itemid'] = $draftid;

        		$key++;
	        }
        }
        
        return $question;
    }

    /**
     * (non-PHPdoc)
     * @see question_edit_form::validation()
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check for empty option texts.
        for ($i = 1; $i <= $this->numberofrows; $i++) {
            $optiontext = $data['option_' . $i]['text'];
            // Remove HTML tags.
            $optiontext = trim(strip_tags($optiontext));
            // Remove newlines.
            $optiontext = preg_replace("/[\r\n]+/i", '', $optiontext);
            // Remove whitespaces and tabs.
            $optiontext = preg_replace("/[\s\t]+/i", '', $optiontext);
            // Also remove UTF-8 non-breaking whitespaces.
            $optiontext = trim($optiontext, "\xC2\xA0\n");
            // Now check whether the string is empty.
            if (empty($optiontext)) {
                $errors['option_' . $i] = get_string('mustsupplyvalue', 'qtype_kprime');
            }
        }

        // Check for empty response texts.
        for ($j = 1; $j <= $this->numberofcolumns; $j++) {
            if (trim(strip_tags($data['responsetext_' . $j])) == false) {
                $errors['responsetext_' . $j] = get_string('mustsupplyvalue', 'qtype_kprime');
            }
        }
        return $errors;
    }

    /**
     * (non-PHPdoc)
     * @see myquestion_edit_form::qtype()
     */
    public function qtype() {
        return 'kprime';
    }
}
