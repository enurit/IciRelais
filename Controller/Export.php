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

namespace IciRelais\Controller;



use IciRelais\IciRelais;
use Thelia\Controller\Admin\BaseAdminController;
use IciRelais\Controller\ExportExaprint;

use IciRelais\Model\OrderAddressIcirelaisQuery;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Model\AddressQuery;
use Thelia\Model\OrderAddressQuery;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderProductQuery;
use Thelia\Model\CustomerQuery;
use Thelia\Model\OrderProductTaxQuery;



/**
 * Class Export
 * @package IciRelais
 * @author benjamin perche <bperche9@gmail.com>
 * @original_author etienne roudeix <eroudeix@openstudio.fr>
 */

class Export extends BaseAdminController 
{
	// FONCTION POUR LE FICHIER D'EXPORT BY Maitre eroudeix@openstudio.fr
    public static function harmonise($value, $type, $len)
    {
        switch($type)
        {
            case 'numeric':
                $value = (string)$value;
                if(mb_strlen($value, 'utf8') > $len);
                    $value = substr($value, 0, $len);
                for($i = mb_strlen($value, 'utf8'); $i < $len; $i++)
                {
                    $value = '0' . $value;
                }
                break;
            case 'alphanumeric':
                $value = (string)$value;
                if(mb_strlen($value, 'utf8') > $len);
                    $value = substr($value, 0, $len);
                for($i = mb_strlen($value, 'utf8'); $i < $len; $i++)
                {
                    $value .= ' ';
                }
                break;
        }
        return $value;
    }
		
	public static function exportfile() {
		if(is_readable(ExportExaprint::getJSONpath())) {
			$admici = json_decode(file_get_contents(ExportExaprint::getJSONpath()),true);
			$keys= array("name", "addr", "addr2", "zipcode","city","tel","mobile","mail","assur");
			$valid = true;
			foreach($keys as $key) {
				$valid &= isset($admici[$key]) && ($key === "assur" ? true:!empty($admici[$key]));
			}
			if(!$valid) {
				return new Response("File IciRelais/Config/exportdat.json is not valid", 500);
			}
		} else {
			return new Response("Can't read IciRelais/Config/exportdat.json", 500);
		}
        $exp_name=$admici['name'];
        $exp_address1=$admici['addr'];
        $exp_address2=$admici['addr2'];
        $exp_zipcode=$admici['zipcode'];
        $exp_city=$admici['city'];
        $exp_phone=$admici['tel'];
        $exp_cellphone=$admici['mobile'];
        $exp_email=$admici['mail'];
        $assur_package=$admici['assur'];
		$res = self::harmonise('$' . "VERSION=110", 'alphanumeric', 12) . "\r\n";
		
		$orders = OrderQuery::create()
			->filterByDeliveryModuleId(IciRelais::getModCode())
			->find();
		
		foreach($orders as $order)
		{
			//Get OrderAddress object - customer's address
			$address = OrderAddressQuery::create()
				->findPK($order->getInvoiceOrderAddressId());
			
			//Get Customer object
			$customer = CustomerQuery::create()
				->findPK($order->getCustomerId());
			
			//Get OrderAddressIciRelais object
			$icirelais_code = OrderAddressIcirelaisQuery::create()
				->findPK($order->getDeliveryOrderAddressId());
			//Get OrderProduct object
			$products = OrderProductQuery::create()
				->filterByOrderId($order->getId())
				->find();
			
			// Get Customer's cellphone
			$cellphone = AddressQuery::create()
				->filterByCustomerId($order->getCustomerId())
				->filterByIsDefault("1")
				->findOne()
				->getCellphone();
			
			//Weigth & price calc
			$weight = 0.0;
			$price = $order->getTotalAmount(0,false); // tax = 0 && include postage = flase
			foreach($products as $p) {
				$weight += ((float)$p->getWeight())*(int)$p->getQuantity();
			}
			$weight = floor($weight*100);
			$assur_price = ($assur_package == 'true') ? $price:0;
			$date_format = date("d/m/Y", $order->getUpdatedAt()->getTimestamp());
			
			$res .= self::harmonise($customer->getRef(), 'alphanumeric', 35);
			$res .= self::harmonise("", 'alphanumeric', 2);
			$res .= self::harmonise($weight, 'numeric', 8);
			$res .= self::harmonise("", 'alphanumeric', 15);
			$res .= self::harmonise($customer->getLastname(), 'alphanumeric', 35);
			$res .= self::harmonise($customer->getFirstname(), 'alphanumeric', 35);
			$res .= self::harmonise($address->getAddress2(), 'alphanumeric', 35);
			$res .= self::harmonise($address->getAddress3(), 'alphanumeric', 35);
			$res .= self::harmonise("", 'alphanumeric', 35);
			$res .= self::harmonise("", 'alphanumeric', 35);
			$res .= self::harmonise($address->getZipcode(), 'alphanumeric', 10);
			$res .= self::harmonise($address->getCity(), 'alphanumeric', 35);
			$res .= self::harmonise("", 'alphanumeric', 10);
			$res .= self::harmonise($address->getAddress1(), 'alphanumeric', 35);
			$res .= self::harmonise("", 'alphanumeric', 10);
			$res .= self::harmonise("F", 'alphanumeric', 3); 							// CODE PAYS DESTINATAIRE PAR DEFAUT F
			$res .= self::harmonise($address->getPhone(), 'alphanumeric', 30);
			$res .= self::harmonise("", 'alphanumeric', 15);
			$res .= self::harmonise($exp_name, 'alphanumeric', 35); 						// DEBUT EXPEDITEUR
			$res .= self::harmonise($exp_address2, 'alphanumeric', 35);
			$res .= self::harmonise("", 'alphanumeric', 140);
			$res .= self::harmonise($exp_zipcode, 'alphanumeric', 10);
			$res .= self::harmonise($exp_city, 'alphanumeric', 35);
			$res .= self::harmonise("", 'alphanumeric', 10);
			$res .= self::harmonise($exp_address1, 'alphanumeric', 35);
			$res .= self::harmonise("", 'alphanumeric', 10);
			$res .= self::harmonise("F", 'alphanumeric', 3);								// CODE PAYS EXPEDITEUR PAR DEFAUT F
			$res .= self::harmonise($exp_phone, 'alphanumeric', 30);
			$res .= self::harmonise("", 'alphanumeric', 35); 							// COMMENTAIRE 1 DE LA COMMANDE
			$res .= self::harmonise("", 'alphanumeric', 35);								// COMMENTAIRE 2 DE LA COMMANDE
			$res .= self::harmonise("", 'alphanumeric', 35);								// COMMENTAIRE 3 DE LA COMMANDE
			$res .= self::harmonise("", 'alphanumeric', 35);								// COMMENTAIRE 3 DE LA COMMANDE
			$res .= self::harmonise($date_format, 'alphanumeric', 10);
			$res .= self::harmonise("", 'numeric', 8); 									// N° COMPTE CHARGEUR ICIRELAIS ?
			$res .= self::harmonise("", 'alphanumeric', 35); 							// CODE BARRE
			$res .= self::harmonise($order->getRef(), 'alphanumeric', 35);
			$res .= self::harmonise("", 'alphanumeric', 29);
			$res .= self::harmonise($assur_price, 'numeric', 9);					// MONTANT DE LA VALEUR MARCHANDE A ASSURER EX: 20 euros -> 000020.00
			$res .= self::harmonise("", 'alphanumeric', 8);
			$res .= self::harmonise($customer->getId(), 'alphanumeric', 35);
			$res .= self::harmonise("", 'alphanumeric', 46);
			$res .= self::harmonise($exp_email, 'alphanumeric', 80);
			$res .= self::harmonise($exp_cellphone, 'alphanumeric', 35);
			$res .= self::harmonise($customer->getEmail(), 'alphanumeric', 80);
			$res .= self::harmonise($cellphone, 'alphanumeric', 35);
			$res .= self::harmonise("", 'alphanumeric', 96);
			$res .= self::harmonise($icirelais_code->getCode(), 'alphanumeric', 8); 			// IDENTIFIANT ESPACE ICIRELAIS
			
			$res .= "\r\n";
		}

        $response = new Response(
            $res,
            200,
            array(
                'Content-Type' => 'application/csv-tab-delimited-table',
                'Content-disposition' => 'filename=export.dat'
            )

        );

        return $response;
	}
}
?>
