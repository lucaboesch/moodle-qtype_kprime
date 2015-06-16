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
 * Version information for the kprime question type.
 *
 * @package     qtype_kprime
 * @author      Juergen Zimmer jzimmer1000@gmail.com
 * @copyright   eDaktik 2014 andreas.hruska@edaktik.at
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'qtype_kprime';
$plugin->version   = 2015062500;
$plugin->requires  = 2013111804; // Moodle >=2.6.4.

$plugin->maturity  = MATURITY_STABLE;
