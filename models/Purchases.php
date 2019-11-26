<?php
class Purchases extends model {

	public function getList($offset, $id_company) {
		$array = array();

		$sql = $this->db->prepare("
			SELECT
				purchases.id,
				purchases.date_purchase,
				purchases.total_price,
				purchases.status,
				clients.name
			FROM purchases
			LEFT JOIN clients ON clients.id = purchases.id_client
			WHERE
				purchases.id_company = :id_company
			ORDER BY purchases.date_purchase DESC
			LIMIT $offset, 10");
		$sql->bindValue(":id_company", $id_company);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$array = $sql->fetchAll();
		}

		return $array;
	}

	public function getAllInfo($id_purchase, $id_company) {
		$array = array();

		$sql = "SELECT * FROM purchases WHERE id = :id";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(":id", $id_purchase);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$array['info'] = $sql->fetch();
		}

		$sql = "SELECT id_product, quant, purchase_price FROM purchases_products WHERE id_purchase = :id_purchase";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(":id_purchase", $id_purchase);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$array['products'] = $sql->fetchAll();

			$i = new Inventory();

			foreach($array['products'] as $pkey => $pval) {

				$array['products'][$pkey]['c'] = $i->getInfo($pval['id_product'], $id_company);

			}
		}

		return $array;
	}

	public function addPurchase($id_company, $id_client, $id_user, $quant, $status) {
		$i = new Inventory();

		$sql = $this->db->prepare("INSERT INTO purchases SET id_company = :id_company, id_client = :id_client, id_user = :id_user, date_purchase = NOW(), total_price = :total_price, status = :status");
		$sql->bindValue(":id_company", $id_company);
		$sql->bindValue(":id_client", $id_client);
		$sql->bindValue(":id_user", $id_user);
		$sql->bindValue(":total_price", '0');
		$sql->bindValue(":status", $status);
		$sql->execute();

		$id_purchase = $this->db->lastInsertId();

		$total_price = 0;
		foreach($quant as $id_prod => $quant_prod) {
			$sql = $this->db->prepare("SELECT price FROM inventory WHERE id = :id AND id_company = :id_company");
			$sql->bindValue(":id", $id_prod);
			$sql->bindValue(":id_company", $id_company);
			$sql->execute();

			if($sql->rowCount() > 0) {
				$row = $sql->fetch();
				$price = $row['price'];

				$sqlp = $this->db->prepare("INSERT INTO purchases_products SET id_company = :id_company, id_purchase = :id_purchase, id_product = :id_product, quant = :quant, purchase_price = :purchase_price");
				$sqlp->bindValue(":id_company", $id_company);
				$sqlp->bindValue(":id_purchase", $id_purchase);
				$sqlp->bindValue(":id_product", $id_prod);
				$sqlp->bindValue(":quant", $quant_prod);
				$sqlp->bindValue(":purchase_price", $price);
				$sqlp->execute();

				$i->decrease($id_prod, $id_company, $quant_prod, $id_user);

				$total_price += $price * $quant_prod;
			}
		}

		$sql = $this->db->prepare("UPDATE purchases SET total_price = :total_price WHERE id = :id");
		$sql->bindValue(":total_price", $total_price);
		$sql->bindValue(":id", $id_purchase);
		$sql->execute();

	}

	public function getInfo($id, $id_company) {
		$array = array();

		$sql = $this->db->prepare("
			SELECT
				*,
				( select clients.name from clients where clients.id = purchases.id_client ) as client_name
			FROM purchases
			WHERE 
				id = :id AND
				id_company = :id_company");
		$sql->bindValue(":id", $id);
		$sql->bindValue(":id_company", $id_company);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$array['info'] = $sql->fetch();
		}

		$sql = $this->db->prepare("
			SELECT
				purchases_products.quant,
				purchases_products.purchase_price,
				inventory.name
			FROM purchases_products
			LEFT JOIN inventory
				ON inventory.id = purchases_products.id_product
			WHERE
				purchases_products.id_purchase = :id_purchase AND
				purchases_products.id_company = :id_company");
		$sql->bindValue(":id_purchase", $id);
		$sql->bindValue(":id_company", $id_company);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$array['products'] = $sql->fetchAll();
		}


		return $array;
	}

	public function changeStatus($status, $id, $id_company) {

		$sql = $this->db->prepare("UPDATE purchases SET status = :status WHERE id = :id AND id_company = :id_company");
		$sql->bindValue(":status", $status);
		$sql->bindValue(":id", $id);
		$sql->bindValue(":id_company", $id_company);
		$sql->execute();

	}

	public function getPurchasesFiltered($client_name, $period1, $period2, $status, $order, $id_company) {

		$array = array();

		$sql = "SELECT
			clients.name,
			purchases.date_purchase,
			purchases.status,
			purchases.total_price
		FROM purchases
		LEFT JOIN clients ON clients.id = purchases.id_client
		WHERE ";

		$where = array();
		$where[] = "purchases.id_company = :id_company";

		if(!empty($client_name)) {
			$where[] = "clients.name LIKE '%".$client_name."%'";
		}

		if(!empty($period1) && !empty($period2)) {
			$where[] = "purchases.date_purchase BETWEEN :period1 AND :period2";
		}

		if($status != '') {
			$where[] = "purchases.status = :status";
		}

		$sql .= implode(' AND ', $where);

		switch($order) {
			case 'date_desc':
			default:
				$sql .= " ORDER BY purchases.date_purchase DESC";
				break;
			case 'date_asc':
				$sql .= " ORDER BY purchases.date_purchase ASC";
				break;
			case 'status':
				$sql .= " ORDER BY purchases.status";
				break;
		}

		$sql = $this->db->prepare($sql);
		$sql->bindValue(":id_company", $id_company);

		if(!empty($period1) && !empty($period2)) {
			$sql->bindValue(":period1", $period1);
			$sql->bindValue(":period2", $period2);
		}

		if($status != '') {
			$sql->bindValue(":status", $status);
		}

		$sql->execute();

		if($sql->rowCount() > 0) {
			$array = $sql->fetchAll();
		}

		return $array;
	}

	public function getTotalRevenue($period1, $period2, $id_company) {
		$float = 0;

		$sql = "SELECT SUM(total_price) as total FROM purchases WHERE id_company = :id_company AND date_purchase BETWEEN :period1 AND :period2";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id_company', $id_company);
		$sql->bindValue(':period1', $period1);
		$sql->bindValue(':period2', $period2);
		$sql->execute();

		$n = $sql->fetch();
		$float = $n['total'];

		return $float;
	}

	public function getTotalExpenses($period1, $period2, $id_company) {
		$float = 0;

		$sql = "SELECT SUM(total_price) as total FROM purchases WHERE id_company = :id_company AND date_purchase BETWEEN :period1 AND :period2";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id_company', $id_company);
		$sql->bindValue(':period1', $period1);
		$sql->bindValue(':period2', $period2);
		$sql->execute();

		$n = $sql->fetch();
		$float = $n['total'];

		return $float;
	}

	public function getSoldProducts($period1, $period2, $id_company) {
		$int = 0;

		$sql = "SELECT id FROM purchases WHERE id_company = :id_company AND date_purchase BETWEEN :period1 AND :period2";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id_company', $id_company);
		$sql->bindValue(':period1', $period1);
		$sql->bindValue(':period2', $period2);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$p = array();
			foreach($sql->fetchAll() as $purchase_item) {
				$p[] = $purchase_item['id'];
			}

			$sql = $this->db->query("SELECT COUNT(*) as total FROM purchases_products WHERE id_purchase IN (".implode(',', $p).")");
			$n = $sql->fetch();
			$int = $n['total'];

		}

		return $int;
	}

	public function getRevenueList($period1, $period2, $id_company) {
		$array = array();
		$currentDay = $period1;
		while($period2 != $currentDay) {
			$array[$currentDay] = 0;
			$currentDay = date('Y-m-d H:i:s', strtotime('+1 day', strtotime($currentDay)));
		}

		$sql = "SELECT DATE_FORMAT(date_purchase, '%Y-%m-%d %H:%i:%d') as date_purchase, SUM(total_price) as total FROM purchases WHERE id_company = :id_company AND date_purchase BETWEEN :period1 AND :period2 GROUP BY DATE_FORMAT(date_purchase, '%Y-%m-%d %H:%i:%d')";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id_company', $id_company);
		$sql->bindValue(':period1', $period1);
		$sql->bindValue(':period2', $period2);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$rows = $sql->fetchAll();

			foreach($rows as $purchase_item) {
				$array[$purchase_item['date_purchase']] = $purchase_item['total'];
			}
		}


		return $array;
	}

	public function getExpensesList($period1, $period2, $id_company) {
		$array = array();
		$currentDay = $period1;
		while($period2 != $currentDay) {
			$array[$currentDay] = 0;
			$currentDay = date('Y-m-d H:i:s', strtotime('+1 day', strtotime($currentDay)));
		}

		$sql = "SELECT DATE_FORMAT(date_purchase, '%Y-%m-%d %H:%i:%d') as date_purchase, SUM(total_price) as total FROM purchases WHERE id_company = :id_company AND date_purchase BETWEEN :period1 AND :period2 GROUP BY DATE_FORMAT(date_purchase, '%Y-%m-%d %H:%i:%d')";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id_company', $id_company);
		$sql->bindValue(':period1', $period1);
		$sql->bindValue(':period2', $period2);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$rows = $sql->fetchAll();

			foreach($rows as $purchase_item) {
				$array[$purchase_item['date_purchase']] = $purchase_item['total'];
			}
		}


		return $array;
	}

	public function getQuantStatusList($period1, $period2, $id_company) {
		$array = array('0'=>0, '1'=>0, '2'=>0);

		$sql = "SELECT COUNT(id) as total, status FROM purchases WHERE id_company = :id_company AND date_purchase BETWEEN :period1 AND :period2 GROUP BY status ORDER BY status ASC";
		$sql = $this->db->prepare($sql);
		$sql->bindValue(':id_company', $id_company);
		$sql->bindValue(':period1', $period1);
		$sql->bindValue(':period2', $period2);
		$sql->execute();

		if($sql->rowCount() > 0) {
			$rows = $sql->fetchAll();

			foreach($rows as $purchase_item) {
				$array[$purchase_item['status']] = $purchase_item['total'];
			}
		}

		return $array;
	}

}









