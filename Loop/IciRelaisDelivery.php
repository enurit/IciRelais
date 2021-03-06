<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace IciRelais\Loop;

use Thelia\Core\Template\Loop\Delivery;
use Thelia\Core\Template\Element\LoopResult;

use IciRelais\IciRelais;
/**
 * Class IciRelaisDelivery
 * @package IciRelais\Loop
 * @author Thelia <info@thelia.net>
 */
class IciRelaisDelivery extends Delivery
{
    public function parseResults(LoopResult $loopResult)
    {
        $icirelaiskey = IciRelais::getModCode();

        $loopResult = parent::parseResults($loopResult);
        for ($loopResult->rewind(); $loopResult->valid(); $loopResult->next()) {
            $loopResult->current()->set("ICI_RELAIS_MODULE", $icirelaiskey);
        }

        return $loopResult;
    }
}
