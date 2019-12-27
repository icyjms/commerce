<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;

class OrderTotalSummary implements OrderTotalSummaryInterface {

  /**
   * The adjustment transformer.
   *
   * @var \Drupal\commerce_order\AdjustmentTransformerInterface
   */
  protected $adjustmentTransformer;

  /**
   * Constructs a new OrderTotalSummary object.
   *
   * @param \Drupal\commerce_order\AdjustmentTransformerInterface $adjustment_transformer
   *   The adjustment transformer.
   */
  public function __construct(AdjustmentTransformerInterface $adjustment_transformer) {
    $this->adjustmentTransformer = $adjustment_transformer;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTotals(OrderInterface $order) {
    $adjustments = $order->collectAdjustments();
    $adjustments = $this->adjustmentTransformer->processAdjustments($adjustments);
    $subtotal = $order->getSubtotalPrice();
    $subtotal_without_tax = $subtotal;
    foreach ($adjustments as $adjustment) {
      if ($adjustment->getType() === 'tax' && $adjustment->isIncluded()) {
        $subtotal_without_tax = $subtotal_without_tax->subtract($adjustment->getAmount());
      }
    }
    // Convert the adjustments to arrays.
    $adjustments = array_map(function (Adjustment $adjustment) {
      return $adjustment->toArray();
    }, $adjustments);
    // Provide the "total" key for backwards compatibility reasons.
    foreach ($adjustments as $index => $adjustment) {
      $adjustments[$index]['total'] = $adjustments[$index]['amount'];
    }

    return [
      'subtotal' => $subtotal,
      'subtotal_without_tax' => $subtotal_without_tax,
      'adjustments' => $adjustments,
      'total' => $order->getTotalPrice(),
    ];
  }

}
