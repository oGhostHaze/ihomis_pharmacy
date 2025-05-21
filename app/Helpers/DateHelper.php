<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    /**
     * Format a date string to a specific format.
     *
     * @param string $dateString The date string to format.
     * @param string $format The format to apply (default: 'Y-m-d H:i:s').
     * @return string The formatted date string.
     */
    public static function formatDate($dateString, $format = 'Y-m-d H:i:s')
    {
        // Convert the date string to a DateTime object
        $date = new \DateTime($dateString);

        // Format the date and return it
        return $date->format($format);
    }


    /**
     * Parse expiry date with various formats
     */
    public static function parseExpiryDate($expiryDate)
    {
        if (empty($expiryDate)) {
            return [
                'raw' => null,
                'formatted' => 'N/A',
                'sql_format' => null
            ];
        }

        try {
            // Remove any leading/trailing whitespace
            $expiryDate = trim($expiryDate);

            // Check different date patterns
            if (preg_match('/^(\d{1,2})\/(\d{4})$/', $expiryDate, $matches)) {
                // Format: 10/2027 or 2/2027
                $month = $matches[1];
                $year = $matches[2];
                $day = 1; // Default to first day of the month
                $parsedDate = Carbon::createFromDate($year, $month, $day);
            } elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $expiryDate, $matches)) {
                // Format: 2/9/2027 (M/D/Y)
                $month = $matches[1];
                $day = $matches[2];
                $year = $matches[3];
                $parsedDate = Carbon::createFromDate($year, $month, $day);
            } elseif (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $expiryDate, $matches)) {
                // Format: 10-01-2029 (M-D-Y or D-M-Y)
                // Try D-M-Y first as it's more common with dates like 10-01-2029
                if ($matches[1] <= 12 && $matches[2] <= 12) {
                    // Ambiguous, default to D-M-Y
                    $day = $matches[1];
                    $month = $matches[2];
                    $year = $matches[3];
                } elseif ($matches[1] > 12) {
                    // Must be D-M-Y since first value is > 12
                    $day = $matches[1];
                    $month = $matches[2];
                    $year = $matches[3];
                } else {
                    // Must be M-D-Y since second value is > 12
                    $month = $matches[1];
                    $day = $matches[2];
                    $year = $matches[3];
                }
                $parsedDate = Carbon::createFromDate($year, $month, $day);
            } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $expiryDate, $matches)) {
                // Format: 2027-02-09 (Y-M-D)
                $year = $matches[1];
                $month = $matches[2];
                $day = $matches[3];
                $parsedDate = Carbon::createFromDate($year, $month, $day);
            } else {
                // Try to parse using Carbon's flexible parser
                $parsedDate = Carbon::parse($expiryDate);
            }

            return [
                'raw' => $expiryDate,
                'formatted' => $parsedDate->format('M d, Y'),
                'sql_format' => $parsedDate->format('Y-m-d')
            ];
        } catch (\Exception $e) {
            // If parsing fails, return the raw value
            return [
                'raw' => $expiryDate,
                'formatted' => $expiryDate,
                'sql_format' => null
            ];
        }
    }
}
