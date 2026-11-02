<?php

namespace App\Services;

/**
 * BillingService
 *
 * Core billing service extending CalculateBillingService for backward compatibility.
 */
class BillingService extends CalculateBillingService
{
    // Inherits all proration, segment calculations, and invoice generation methods
}
