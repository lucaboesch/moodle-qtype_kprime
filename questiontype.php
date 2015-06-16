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

global $CFG;
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/type/kprime/lib.php');


/**
 * The kprime question type.
 *
 */
class qtype_kprime extends question_type {

    /**
     * Sets the default options for the question.
     *
     * (non-PHPdoc)
     * @see question_type::set_default_options()
     */
    public function set_default_options($question) {
        $kprimeconfig = get_config('qtype_kprime');

        if (!isset($question->options)) {
            $question->options = new stdClass();
        }
        if (!isset($question->options->numberofrows)) {
            $question->options->numberofrows = QTYPE_KPRIME_NUMBER_OF_OPTIONS;
        }
        if (!isset($question->options->numberofcolumns)) {
            $question->options->numberofcolumns = QTYPE_KPRIME_NUMBER_OF_RESPONSES;
        }
        if (!isset($question->options->shuffleoptions)) {
            $question->options->shuffleoptions = $kprimeconfig->shuffleoptions;
        }
        if (!isset($question->options->scoringmethod)) {
            $question->options->scoringmethod = $kprimeconfig->scoringmethod;
        }
        if (!isset($question->options->rows)) {
            $rows = array();
            for ($i = 1; $i <= $question->options->numberofrows; $i++) {
                $row = new stdClass();
                $row->number = $i;
                $row->optiontext = '';
                $row->optiontextformat = FORMAT_HTML;
                $row->optionfeedback = '';
                $row->optionfeedbackformat = FORMAT_HTML;
                $rows[] = $row;
            }
            $question->options->rows = $rows;
        }

        if (!isset($question->options->columns)) {
            $columns = array();
            for ($i = 1; $i <= $question->options->numberofcolumns; $i++) {
                $column = new stdClass();
                $column->number = $i;
                $column->responsetext = $kprimeconfig->{'responsetext' . $i};
                $column->responsetextformat = FORMAT_MOODLE;
                $columns[] = $column;
            }
            $question->options->columns = $columns;
        }
    }

    /**
     * Loads the question options, rows, columns and weights from the database.
     *
     * (non-PHPdoc)
     * @see question_type::get_question_options()
     */
    public function get_question_options($question) {
        global $DB, $OUTPUT;

        parent::get_question_options($question);

        // Retrieve the question options.
        $question->options = $DB->get_record('qtype_kprime_options', array('questionid' => $question->id));
        // Retrieve the question rows (kprime options).
        $question->options->rows = $DB->get_records('qtype_kprime_rows',
                array('questionid' => $question->id), 'number ASC', '*', 0, $question->options->numberofrows);
        // Retrieve the question columns.
        $question->options->columns = $DB->get_records('qtype_kprime_columns',
                array('questionid' => $question->id), 'number ASC', '*', 0, $question->options->numberofcolumns);

        $weightrecords = $DB->get_records('qtype_kprime_weights',
                array('questionid' => $question->id), 'rownumber ASC, columnnumber ASC');

        foreach ($question->options->rows as $key => $row) {
            $question->{'option_' . $row->number}['text'] = $row->optiontext;
            $question->{'option_' . $row->number}['format'] = $row->optiontextformat;
            $question->{'feedback_' . $row->number}['text'] = $row->optionfeedback;
            $question->{'feedback_' . $row->number}['format'] = $row->optionfeedbackformat;
        }

        foreach ($question->options->columns as $key => $column) {
            $question->{'responsetext_' . $column->number} = $column->responsetext;
        }

        foreach ($weightrecords as $key => $weight) {
            if ($weight->weight == 1.0) {
                $question->{'weightbutton_' . $weight->rownumber} = $weight->columnnumber;
            }
        }
        // Put the weight records into an array indexed by rownumber and columnnumber.
        $question->options->weights = $this->weight_records_to_array($weightrecords);
        return true;
    }

    /**
     * Stores the question options in the database.
     *
     * (non-PHPdoc)
     * @see question_type::save_question_options()
     */
    public function save_question_options($question) {
        global $DB;

        $context = $question->context;
        $result = new stdClass();

        // Insert all the new options.
        $options = $DB->get_record('qtype_kprime_options', array('questionid' => $question->id));
        if (!$options) {
            $options = new stdClass();
            $options->questionid = $question->id;
            $options->scoringmethod = '';
            $options->shuffleoptions = '';
            $options->numberofcolumns = '';
            $options->numberofrows = '';
            $options->id = $DB->insert_record('qtype_kprime_options', $options);
        }

        $options->scoringmethod = $question->scoringmethod;
        $options->shuffleoptions = $question->shuffleoptions;
        $options->numberofrows = $question->numberofrows;
        $options->numberofcolumns = $question->numberofcolumns;
        $DB->update_record('qtype_kprime_options', $options);

        $this->save_hints($question, true);

        // Insert all the new rows.
        $oldrows = $DB->get_records('qtype_kprime_rows',
                array('questionid' => $question->id), 'number ASC');

        for ($i = 1; $i <= $options->numberofrows; $i++) {
            $row = array_shift($oldrows);
            if (!$row) {
                $row = new stdClass();
                $row->questionid = $question->id;
                $row->number = $i;
                $row->optiontext = '';
                $row->optiontextformat = FORMAT_HTML;
                $row->optionfeedback = '';
                $row->optionfeedbackformat = FORMAT_HTML;

                $row->id = $DB->insert_record('qtype_kprime_rows', $row);
            }

            // Also save images in optiontext and feedback. 
			$optiondata = $question->{'option_' . $i};	
            $row->optiontext = $this->import_or_save_files($optiondata,
                    $context, 'qtype_kprime', 'optiontext', $row->id);
            $row->optiontextformat = $question->{'option_' . $i}['format'];
            $optionfeedback = $question->{'feedback_' . $i};
            $row->optionfeedback = $this->import_or_save_files($optionfeedback,
                    $context, 'qtype_kprime', 'feedbacktext', $row->id);
            $row->optionfeedbackformat = $question->{'feedback_' . $i}['format'];

            $DB->update_record('qtype_kprime_rows', $row);
        }

// TODO put this when adding changeable numbers of rows.
// //Delete old row records.
//         $fs = get_file_storage();
//         foreach ($oldrows as $oldrow) {
//             $fs->delete_area_files($context->id, 'qtype_kprime', 'option', $oldrow->id);
//             $DB->delete_records('qtype_kprimw_rows', array('id' => $oldrow->id));
//         }        

        $oldcolumns = $DB->get_records('qtype_kprime_columns',
                array('questionid' => $question->id), 'number ASC');

        // Insert all new columns.
        for ($i = 1; $i <= $options->numberofcolumns; $i++) {
            $column = array_shift($oldcolumns);
            if (!$column) {
                $column = new stdClass();
                $column->questionid = $question->id;
                $column->number = $i;
                $column->responsetext = '';
                $column->responsetextformat = FORMAT_MOODLE;

                $column->id = $DB->insert_record('qtype_kprime_columns', $column);
            }

            // Perform an update.
            $column->responsetext = $question->{'responsetext_' . $i};
            $column->responsetextformat = FORMAT_PLAIN;

            $DB->update_record('qtype_kprime_columns', $column);
        }

        // Set all the new weights.
        $oldweightrecords = $DB->get_records('qtype_kprime_weights', array('questionid' => $question->id),
                'rownumber ASC, columnnumber ASC');

        // Put the old weights into an array.
        $oldweights = $this->weight_records_to_array($oldweightrecords);

        for ($i = 1; $i <= $options->numberofrows; $i++) {
            for ($j = 1; $j <= $options->numberofcolumns; $j++) {
                if (!empty($oldweights[$i][$j])) {
                    $weight = $oldweights[$i][$j];
                } else {
                    $weight = new stdClass();
                    $weight->questionid = $question->id;
                    $weight->rownumber = $i;
                    $weight->columnnumber = $j;
                    $weight->weight = 0.0;
                    $weight->id = $DB->insert_record('qtype_kprime_weights', $weight);
                }

                // Perform the weight update.
                if (property_exists($question, 'weightbutton_' . $i)) {
                    if ($question->{'weightbutton_' . $i} == $j) {
                        $weight->weight = 1.0;
                    } else {
                        $weight->weight = 0.0;
                    }
                } else {
                    $weight->weight = 0.0;
                }
                $DB->update_record('qtype_kprime_weights', $weight);
            }
        }
    }

    /**
     * Initialise the common question_definition fields.
     *
     * @param question_definition $question the question_definition we are creating.
     * @param object $questiondata the question data loaded from the database.
     */
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);

        $question->shuffleoptions = $questiondata->options->shuffleoptions;
        $question->scoringmethod = $questiondata->options->scoringmethod;
        $question->numberofrows = $questiondata->options->numberofrows;
        $question->numberofcolumns = $questiondata->options->numberofcolumns;
        $question->rows = $questiondata->options->rows;
        $question->columns = $questiondata->options->columns;
        $question->weights = $questiondata->options->weights;
    }

    /**
     * Custom method for deleting kprime questions.
     *
     * (non-PHPdoc)
     * @see question_type::delete_question()
     */
    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('qtype_kprime_options', array('questionid' => $questionid));
        $DB->delete_records('qtype_kprime_rows',    array('questionid' => $questionid));
        $DB->delete_records('qtype_kprime_columns', array('questionid' => $questionid));
        $DB->delete_records('qtype_kprime_weights', array('questionid' => $questionid));
        parent::delete_question($questionid, $contextid);
    }

    /**
     * Turns an array of records from the table qtype_kprime_weights into an array of array indexed by rows and columns.
     *
     * @param unknown $weightrecords
     * @return Ambigous <multitype:multitype: , unknown>
     */
    private function weight_records_to_array($weightrecords) {
        $weights = array();
        foreach ($weightrecords as $id => $weight) {
            if (!array_key_exists($weight->rownumber, $weights)) {
                $weights[$weight->rownumber] = array();
            }
            $weights[$weight->rownumber][$weight->columnnumber] = $weight;
        }
        return $weights;
    }

    /**
     * (non-PHPdoc)
     * @see question_type::get_random_guess_score()
     */
    public function get_random_guess_score($questiondata) {
        $scoring = $questiondata->options->scoringmethod;
        if ($scoring == 'kprime') {
                return 0.1875;            
        } else if ($scoring == 'kprimeonezero') {
                return 0.0625;
        } else if ($scoring == 'subpoints') {
                return 0.5;
        } else {
            return 0.00;
        }
    }

    /**
     *
     * (non-PHPdoc)
     * @see question_type::get_possible_responses()
     */
    public function get_possible_responses($questiondata) {
        $question = $this->make_question($questiondata);
		$weights = $question->weights; 
        $parts = array();
        foreach ($question->rows as $rowid => $row) {
            $choices = array();
            foreach ($question->columns as $columnid => $column) {
                $partialcredit = 0.0;
                if ($question->scoringmethod == 'subpoints' && $weights[$row->number][$column->number]->weight > 0) {
                     $partialcredit = 1 / count($question->rows);
                }
                $choices[$columnid] = 
                new question_possible_response(html_to_text($row->optiontext, $row->optiontextformat, false) .
                		 ": " . html_to_text($column->responsetext, $column->responsetextformat),
                        $partialcredit);
            }
            $choices[null] = question_possible_response::no_response();

            $parts[$rowid] = $choices;
        }

        return $parts;
    }

    /**
     * (non-PHPdoc)
     * @see question_type::move_files()
     */
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_options_and_feedback($questionid, $oldcontextid, $newcontextid, true);
    }

    /**
     * (non-PHPdoc)
     * @see question_type::delete_files()
     */
    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_options_and_feedback($questionid, $contextid);
    }

    /**
     * Move all the files belonging to this question's options and feedbacks 
     * when the question is moved from one context to another.
     * @param int $questionid the question being moved.
     * @param int $oldcontextid the context it is moving from.
     * @param int $newcontextid the context it is moving to.
     * @param bool $answerstoo whether there is an 'answer' question area,
     *      as well as an 'answerfeedback' one. Default false.
     */
    protected function move_files_in_options_and_feedback($questionid, $oldcontextid,
    		$newcontextid, $answerstoo = false) {
    	global $DB;

    	$fs = get_file_storage();

    	$rowids = $DB->get_records_menu('qtype_kprime_rows',
    			array('question' => $questionid), 'id', 'id,1');
    	foreach ($rowids as $rowid => $notused) {
    		$fs->move_area_files_to_new_context($oldcontextid,
    				$newcontextid, 'qtype_kprime', 'optiontext', $rowid);
    		$fs->move_area_files_to_new_context($oldcontextid,
    				$newcontextid, 'qtype_kprime', 'feedbacktext', $rowid);
    	}
    }
    
    /**
     * Delete all the files belonging to this question's options and feedback. 
     * 
     * @param unknown $questionid
     * @param unknown $contextid
     */
    protected function delete_files_in_options_and_feedback($questionid, $contextid) {
        global $DB;
        $fs = get_file_storage();

    	$rowids = $DB->get_records_menu('qtype_kprime_rows',
    			array('questionid' => $questionid), 'id', 'id,1');

    	foreach ($rowids as $rowid => $notused) {
    		$fs->delete_area_files($contextid, 'qtype_kprime', 'optiontext', $rowid);
    		$fs->delete_area_files($contextid, 'qtype_kprime', 'feedbacktext', $rowid);
    	}
    }
    /**
     * Export to XML. 
     * 
     * @param unknown $question
     * @param unknown qformat_xml format
     * @param unknown extra	 
     */
    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        $fs = get_file_storage();
        $contextid = $question->contextid;
        $output = '';
	
        if ($question->options->scoringmethod) {
            $output .= '    <scoringmethod>'.$question->options->scoringmethod.'</scoringmethod>';
			$output .= "\n";
        }		
        if ($question->options->shuffleoptions) {
            $output .= '    <shuffleoptions>'.$question->options->shuffleoptions.'</shuffleoptions>';
			$output .= "\n";
        }
        if ($question->options->numberofrows) {
            $output .= '    <numberofrows>'.$question->options->numberofrows.'</numberofrows>';
			$output .= "\n";
        }	
        if ($question->options->numberofcolumns) {
            $output .= '    <numberofcolumns>'.$question->options->numberofcolumns.'</numberofcolumns>';
			$output .= "\n";
        }
		if($question->options->optiontextformat == 1){
			$question->generalfeedbackformat = 1;
		}
		/* does not exsist in krpime?
        $output .= $format->write_combined_feedback($question->options,
                                                    $question->id,
                                                    $question->contextid);
		*/
		$output .= "    <rows>\n";
        foreach ($question->options->rows as $row) {
            $output .= "        <row>\n";
            $output .= "          <number>{$row->number}</number>\n";
			$output .= "          <optiontext>{$format->xml_escape($row->optiontext)}</optiontext>\n";
			$output .= "          <optiontextformat>{$row->optiontextformat}</optiontextformat>\n";
			$output .= "          <optionfeedback>{$format->xml_escape($row->optionfeedback)}</optionfeedback>\n";
			$output .= "          <optionfeedbackformat>{$row->optionfeedbackformat}</optionfeedbackformat>\n";			
            $output .= "        </row>\n";
        }
		$output .= "    </rows>\n";
		$output .= "    <columns>\n";
        foreach ($question->options->columns as $column) {
            $output .= "        <column>\n";
            $output .= "          <number>{$column->number}</number>\n";
			$output .= "          <responsetext>{$format->xml_escape($column->responsetext)}</responsetext>\n";
			$output .= "          <responsetextformat>{$column->responsetextformat}</responsetextformat>\n";		
            $output .= "        </column>\n";
        }
		$output .= "    </columns>\n";
		$output .= "    <weights>\n";
        foreach ($question->options->weights as $weight) {   
			foreach ($weight as $weightval){
				$output .= "        <weight>\n";
				$output .= "              <rownumber>{$weightval->rownumber}</rownumber>\n";
				$output .= "              <columnnumber>{$weightval->columnnumber}</columnnumber>\n";
				$output .= "              <weight>{$weightval->weight}</weight>\n";		
				$output .= "        </weight>\n";
			}
        }
		$output .= "    </weights>\n";
        return $output;
    }
    /**
     * Import from XML. 
     * 
     * @param unknown $data	 
     * @param unknown $question
     * @param unknown qformat_xml format
     * @param unknown extra	 
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        if (!isset($data['@']['type']) || $data['@']['type'] != 'kprime') {
            return false;
        }

        $question = $format->import_headers($data);
        $question->qtype = 'kprime';

        $question->scoringmethod = $format->getpath($data, array('#', 'scoringmethod', 0, '#'), 'kprime');
		$question->shuffleoptions = $format->getpath($data, array('#', 'shuffleoptions', 0, '#'), '1');
		$question->numberofrows = $format->getpath($data, array('#', 'numberofrows', 0, '#'), 0);
		$question->numberofcolumns = $format->getpath($data, array('#', 'numberofcolumns', 0, '#'), 0);

		// now dirty loops to dig to deepest nodes
		
        // get rows
		$rows = $format->getpath($data, array('#', 'rows', 0, '#'), ''); 
		//$format->import_text_with_files($subqxml, array(), '', $format->get_format($question->questiontextformat));
		$question->rows = array();
		foreach ( $rows as $row ) {
			foreach($row as $row_sub){
				foreach($row_sub as $row_sub_sub){
					$qr = array();
					$qr['number'] = $row_sub_sub['number'][0]['#'];
					$qr['optiontext'] = $row_sub_sub['optiontext'][0]['#'];	
					$qr['optiontextformat'] = $row_sub_sub['optiontextformat'][0]['#'];
					$qr['optionfeedback'] = $row_sub_sub['optionfeedback'][0]['#'];
					$qr['optionfeedbackformat'] = $row_sub_sub['optionfeedbackformat'][0]['#'];
					$question->rows[] = $qr;					
				}
			}
			
		}//print_r($question->options->rows);exit;
        // get cols
		$columns = $format->getpath($data, array('#', 'columns', 0, '#'), '');
		$question->columns = array();
		foreach ( $columns as $column ) {
			foreach($column as $column_sub){
				foreach($column_sub as $column_sub_sub){
					$qc = array();
					$qc['number'] = $column_sub_sub['number'][0]['#'];
					$qc['responsetext'] = $column_sub_sub['responsetext'][0]['#'];	
					$qc['responsetextformat'] = $column_sub_sub['responsetextformat'][0]['#'];
					$question->columns[] = $qc;					
				}
			}
			
		}
        // get weights
		$weights = $format->getpath($data, array('#', 'weights', 0, '#'), '');
		$question->weights = array();
		foreach ( $weights as $weight ) {
			foreach($weight as $weight_sub){
				foreach($weight_sub as $weight_sub_sub){
					$qw = array();
					$qw['rownumber'] = $weight_sub_sub['rownumber'][0]['#'];
					$qw['columnnumber'] = $weight_sub_sub['columnnumber'][0]['#'];	
					$qw['weight'] = $weight_sub_sub['weight'][0]['#'];
					$question->weights[] = $qw;					
				}
			}
			
		}
        $format->import_combined_feedback($question, $data, true);
        $format->import_hints($question, $data, true);

        return $question;
    }	
}
