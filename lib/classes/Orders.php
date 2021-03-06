<?php
/**
 * Description of Orders
 *
 * @author Neithan
 */
class Orders
{
	/**
	 * @var array
	 */
	protected $orderList;

	/**
	 * @var array
	 */
	protected $sellingForList;

	function __construct($userId, $orderBy)
	{
		$this->orderList = array();
		$this->sellingForList = array();

		$this->fillOrders($userId, $orderBy);
	}

	/**
	 * Fetch all orders for a user.
	 *
	 * @param integer $userId
	 */
	protected function fillOrders($userId, $orderBy)
	{
		if (!$orderBy)
			$orderBy = 'typeName';

		$sql = '
			SELECT
				eo.`orderId`,
				it.`typeName`,
				eo.amount * eo.price AS sum
			FROM es_orders AS eo
			JOIN invTypes AS it ON (it.`typeID` = eo.`itemId`)
			WHERE eo.`userId` = '.sqlval($userId).'
				AND eo.amountSold < eo.amount
				AND NOW() <= eo.`endDatetime`
				AND !eo.deleted
			ORDER BY '.sqlval($orderBy, false).'
		';
		$orders = query($sql, true);

		if ($orders)
		{
			foreach ($orders as $order)
			{
				$orderObj = Model\Order::getOrderById($order['orderId']);
				$this->orderList[$order['orderId']] = $orderObj;
				$this->addSellingFor($orderObj->getSellingForUser());
			}
		}
	}

	/**
	 * Add a username to the selling for list.
	 *
	 * @param string $sellingFor
	 */
	protected function addSellingFor($sellingFor)
	{
		if (in_array($sellingFor, $this->sellingForList))
			return;

		$this->sellingForList[] = $sellingFor;
	}

	/**
	 * Get an array of \Model\Order
	 *
	 * @return array
	 */
	public function getOrderList()
	{
		return $this->orderList;
	}

	/**
	 * Get an array of the users for which orders are present.
	 *
	 * @return array
	 */
	public function getSellingForList()
	{
		return $this->sellingForList;
	}

	/**
	 * Get all orders for the specified selling for user.
	 *
	 * @param string $sellingForUser
	 * @return array
	 */
	public static function getOrdersBySellingFor($userId, $sellingForUser, $orderBy)
	{
		if (!$orderBy)
			$orderBy = 'itemId';

		$sql = '
			SELECT
				eo.orderId,
				it.typeName,
				amount * price AS sum
			FROM es_orders AS eo
			JOIN invTypes AS it ON (it.`typeID` = eo.`itemId`)
			WHERE eo.`userId` = '.sqlval($userId).'
				AND eo.sellingForUser = '.sqlval($sellingForUser).'
				AND eo.amountSold < eo.amount
				AND NOW() <= eo.`endDatetime`
				AND !eo.deleted
			ORDER BY '.sqlval($orderBy, false).'
		';
		$data = query($sql, true);

		$orders = array();
		foreach ($data as $order)
			$orders[] = Model\Order::getOrderById($order['orderId']);

		return $orders;
	}

	/**
	 * Get a list of all currently active orders.
	 *
	 * @param integer $userId
	 * @return array
	 */
	public static function getOrderIdList($userId)
	{
		$sql = '
			SELECT `orderId`
			FROM es_orders
			WHERE `userId` = '.sqlval($userId).'
				AND amountSold < amount
				AND NOW() <= `endDatetime`
				AND !deleted
		';
		$data = query($sql);

		$orderIdList = array();
		if ($data)
			foreach ($data as $row)
				$orderIdList[$row['orderId']] = $row['orderId'];

		return $orderIdList;
	}
}
