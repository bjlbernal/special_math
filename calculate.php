<?php
// Constants
// Stock Analysis Form Option Values
@define('STOCK_ANALYSIS_FORM_TECHNICAL_BUYSELL_HISTORY',       '1');
@define('STOCK_ANALYSIS_FORM_TECHNICAL_BUYSELL_INDIVIDUAL',    '2');
@define('STOCK_ANALYSIS_FORM_TECHNICAL_BUYSELL_LIST',          '3');
@define('STOCK_ANALYSIS_FORM_TECHNICAL_BUYSELL_NEWSLETTER',    '4');
@define('STOCK_ANALYSIS_FORM_FUNDIMENTAL_BUYSELL_HISTORY',     '5');
@define('STOCK_ANALYSIS_FORM_FUNDIMENTAL_BUYSELL_INDIVIDUAL',  '6');
@define('STOCK_ANALYSIS_FORM_FUNDIMENTAL_BUYSELL_LIST',        '7');
@define('STOCK_ANALYSIS_FORM_FUNDIMENTAL_BUYSELL_NEWSLETTER',  '8');

// Form Option Labels
@define('TECHNICAL_BUYSELL_HISTORY',       'Technical Buy/Sell - History');
@define('TECHNICAL_BUYSELL_INDIVIDUAL',    'Technical Buy/Sell - Individual');
@define('TECHNICAL_BUYSELL_LIST',          'Technical Buy/Sell - List');
@define('TECHNICAL_BUYSELL_NEWSLETTER',    'Technical Buy/Sell - Newsletter');
@define('FUNDIMENTAL_BUYSELL_INDIVIDUAL',  'Fundimental Buy/Sell - Individual');
@define('FUNDIMENTAL_BUYSELL_LIST',        'Fundimental Buy/Sell - List');
@define('FUNDIMENTAL_BUYSELL_NEWSLETTER',  'Fundimental Buy/Sell - Newsletter');

// Interval Option Values
@define('INTERVAL_DAILY',         'd');
@define('INTERVAL_WEEKLY',        'w');
@define('INTERVAL_MONTHLY',       'm');
@define('INTERVAL_DIVIDEND_ONLY', 'v');

// Interval Option Labels
@define('DAILY',          'Daily');
@define('WEEKLY',         'Weekly');
@define('MONTHLY',        'Monthly');
@define('DIVIDEND_ONLY',  'Dividend Only');

// Simple Moving Average Range Constants
@define('SMA_SHORT_RANGE',  20);
@define('SMA_MEDIUM_RANGE', 50);
@define('SMA_LONG_RANGE',   100);

// Moving Average Convergence Divergence Periods Constants
@define('MACD_SLOW_PERIODS',   26);
@define('MACD_FAST_PERIODS',   12);
@define('MACD_SIGNAL_PERIODS', 9);

// Stochastic Constants
@define('STOCHASTIC_PERIODS', 14);
@define('STOCHASTIC_SMOOTHING_FACTOR', 3);

// Single Line Indicator Constants
@define('ATR_PERIODS', 14);
@define('RSI_PERIODS', 14);
@define('CRS_PERIODS', 14);

// Comparative Relative Strength Market Constant
@define('CRS_MARKET', '$SPX');

class Calculate
{
  /**
   * Calculates the average trading range
   *
   * @access public
   * @param  array    $data
   * @param  integer  $key
   * @param  integer  $periods
   * @return float
   */
  public function atr($data, $key, $periods)
  {
    // If there are not enough periods to do the math, leave.
    if ($key+1 < $periods) {
      return null;
    }

    // Define the starting point
    $atr = 0;

    // Loop over each of the periods starting at the declared key until the given number of periods is reached.
    for ($i=$key; ($i>$key-$periods); $i--) {
      // Determine the trading range of the day and add it to the total.
      $atr += $data[$i][2] - $data[$i][3];
    }

    // Average the total
    $atr = $atr/$periods;

    // Return the Average Trading Range

    return $atr;
  }

  /**
   * Calculates the average volume
   * @access public
   * @param  array   $data
   * @param  integer $periods
   * @param  float $previous
   * @return float
   */
  public function averageVolume($data, $periods, $previous=null){
    if(!$previous){
      $label = (isset($data[0]['volume'])) ? 'volume' : 'Volume';
      $ema = $this->ema($data, $periods, $label, $previous);
      return number_format($ema, 0, '.', '');
    }
    else{
      $smoothing_factor = 2/($periods+1);
      $last_volume = (isset($data[count($data)-1]['volume'])) ? $data[count($data)-1]['volume'] : $data[count($data)-1]['Volume'];
      $ema = ($last_volume - $previous) * $smoothing_factor + $previous;
      return number_format($ema, 0, '.', '');
    }
    return false;
  }

  /**
   * Calculates the Bollinger Bands information
   *
   * @access public
   * @param  array    $data
   * @param  integer  $key
   * @return array
   */
  public function bollingerBands($data, $key)
  {
    $bollinger_bands = array('Upper' => 0,
                             'Lower' => 0,
                             'MA'    => 0
                             );

    // Calculate the Simple Moving Average
    $sma = $this->sma($data, $key, 10);

    // Calculate the Median Price for the period range.
    $mp = $this->medianPrice($data, $key, 10);
    if ($mp) {
      // If the Median Price is calculable.

      // Calculate the Standard Deviation
      $sd = $this->standardDeviation($mp, $sma, 10);

      // Calculate the split adjustment
      $split_adj = round(($data[$key][4]/$data[$key][6]));

      // Calculate the Upper Bollinger Band, adjusted for splits
      $bollinger_bands['Upper'] = ($sma+2*$sd)/$split_adj;

      // Calculate the Lower Bollinger Band, adjusted for splits
      $bollinger_bands['Lower'] = ($sma-2*$sd)/$split_adj;

      // Supply the moving average.
      $bollinger_bands['MA']  = ($sma)/$split_adj;
    }

    return $bollinger_bands;
  }

  /**
   * Calculates the exponential moving average
   *
   * @access public
   * @param  float    $data
   * @param  integer  $periods
   * @param  string   $label
   * @param  float    $ema_y
   * @return float
   */
  public function ema($data, $periods, $label, $ema_y=null)
  {
    $ema_t = 0;

    if (empty($ema_y)) {
      // Calculate the ema as sma
      $ema_t = $this->sma($data, $periods, $label);
    }
    else {
      // Calculate a smoothing factor
      $smoothing_factor = (float)(2/($periods+1));

      // Today's ema is an adjustment over yesterday's.
      $ema_t = ($data[count($data)-1][$label]*$smoothing_factor)+($ema_y*(1-$smoothing_factor));
    }

    return number_format($ema_t, 4, '.', '');
  }

  /**
   * Calculates the Moving Average Convergence Divergence indicator
   *
   * @access public
   * @param  array    $data
   * @param  integer  $macd_fast_periods
   * @param  integer  $macd_slow_periods
   * @param  float    &$ema1_y
   * @param  float    &$ema2_y
   * @param  float    $macd_prev
   * @return float
   */
  public function macd($data, $macd_fast_periods, $macd_slow_periods, &$ema1_y, &$ema2_y, $macd_prev=null)
  {
    $macd_array = array();

    // Recalculate the previous day MACD
    if(empty($macd_prev['macd'])){
      $macd_prev['macd'] = $ema1_y-$ema2_y;
    }

    // Calculate the new exponential moving averages
    // The new $ema# will become tomorrow $ema#_y
    $ema1_y = $ema1 = $this->ema($data, $macd_fast_periods, 'close', $ema1_y);
    $ema2_y = $ema2 = $this->ema($data, $macd_slow_periods, 'close', $ema2_y);

    // Calculate today's MACD
    $data[count($data)-1]['macd'] = $macd = $ema1-$ema2;

    // Calculate the MACD EMA signal line
    if(count($data)-1<MACD_SIGNAL_PERIODS){
      $slice = array_slice($data, 0, count($data)-1);
    }
    else{
      $slice = array_slice($data, count($data)-MACD_SIGNAL_PERIODS, MACD_SIGNAL_PERIODS);
    }
    $signal = $this->ema($slice, MACD_SIGNAL_PERIODS, 'macd', $macd_prev['ema']);

    // Calculate the Divergence
    $divergence = $macd-$signal;

    $macd_array['macd']       = number_format($macd, 4, '.', '');
    $macd_array['ema']        = number_format($signal, 4, '.', '');
    $macd_array['divergence'] = number_format($divergence, 4, '.', '');

    return $macd_array;
  }

  /**
   * Calculates the median price value
   *
   * @access public
   * @param  array    $data
   * @param  integer  $key
   * @param  integer  $periods
   * @return float
   */
  public function medianPrice($data, $key, $periods)
  {
    // If there are not enough periods to do the math, leave.
    if ($key+1 < $periods) {
      return null;
    }

    // Loop over the periods to calculate against.
    for ($i=$key; $i>$key-$periods; $i--) {
      // Get the lowest low
      if (empty($lowest_low)) {
        $lowest_low = $data[$i][3];
      }
      elseif ($lowest_low > $data[$i][3]) {
        $lowest_low = $data[$i][3];
      }

      // Get the highest high
      if (empty($highest_high)) {
         $highest_high = $data[$i][2];
      }
      elseif ($highest_high < $data[$i][2]) {
        $highest_high = $data[$i][2];
      }
    }

    // Return the median price value ((Recent Close + Lowest Low + Highest High)/3)
    return (($data[$key][4] + $lowest_low + $highest_high)/3);
  }

  /**
   * Calculates the Parabolic Support and Resistance indicator.
   *
   * @access  public
   * @param   $processed_data
   * @param   $row
   * @param   &$sp
   * @param   &$ep
   * @return  float
   */
  public function parabolicSAR($processed_data, $row, &$sp, &$ep)
  {
    $split_adj = 1;

    // Make an adjustment for all price splits.
    if ($row['Close'] > $row['AdjClose']) $split_adj = $row['Close']/$row['AdjClose'];
    
    // If we have a previous Parabolic SAR
    if (isset($processed_data) and isset($processed_data[(count($processed_data)-1)]['ParabolicSAR'])) {
      // Define the extreme point.
      $ep = $this->_parabolicSARExtremePoint($processed_data[(count($processed_data)-1)]['ParabolicSAR'], $ep, $row, $split_adj);

      // Calculate tomorrows Parabolic SAR and store it in todays record.
      $psar_today     = isset($processed_data[(count($processed_data)-1)]['ParabolicSAR']) ? $processed_data[(count($processed_data)-1)]['ParabolicSAR'] : $row['AdjClose'];
      $parabolic_sar  = $psar_today + ($sp*($ep-$psar_today));

      list($parabolic_sar, $ep, $sp) = $this->_parabolicSARExtremePointAndAccellerationFactorForTomorrow($processed_data, $row, $split_adj, $parabolic_sar, $ep, $sp);
    }
    else {
      // Default the first Parabolic SAR as the first Adj Close price.
      $parabolic_sar = $row['AdjClose'];
    }

    return $parabolic_sar;
  }

  /**
   * Calculates the Parabolic SAR Extreme Point
   *
   * @access private
   * @param  float $psar
   * @param  float $ep
   * @param  array $row
   * @param  float $split_adj
   * @return float
   */
  private function _parabolicSARExtremePoint($psar, $ep, $row, $split_adj){

    // Define the extreme point.
    if (is_null($ep) && $row['AdjClose'] < $psar) {
      // In a down trend.
      $ep = ($row['Low']/$split_adj);
    }
    elseif (is_null($ep) && $row['AdjClose'] > $psar) {
      // In an up trend.
      $ep = ($row['High']/$split_adj);
    }

    return $ep;
  }

  /**
   * Calculates the Parabolic SAR, Extreme Point and Accelleration Factor for Tomorrows calculations.
   *
   * @access private
   * @param  array $processed_data
   * @param  array $row
   * @param  float $split_adj
   * @param  float $parabolic_sar $ep, $sp
   * @param  float $ep
   * @param  float $sp
   * @return array
   */
  private function _parabolicSARExtremePointAndAccellerationFactorForTomorrow($processed_data, $row, $split_adj, $parabolic_sar, $ep, $sp) {
    $sp_init = 0.02;
    $sp_max  = 0.2;

    if ($processed_data[(count($processed_data)-1)]['ParabolicSAR'] <= ($processed_data[(count($processed_data)-1)]['Low']/$split_adj)) {
      // In an up-trend
      if (($parabolic_sar > ($row['Low']/$split_adj)) or
          ($parabolic_sar > ($processed_data[(count($processed_data)-1)]['Low']/$split_adj))) {
        // And PSAR is higher than it should be.
        $parabolic_sar  = ($processed_data[(count($processed_data)-1)]['High']/$split_adj);
        $ep             = 0;
        $sp             = $sp_init;
      }
    }
    elseif ($processed_data[(count($processed_data)-1)]['ParabolicSAR'] >= ($processed_data[(count($processed_data)-1)]['High']/$split_adj)) {
      // In a down-trend
      if (($parabolic_sar < ($row['High']/$split_adj)) or
          ($parabolic_sar < ($processed_data[(count($processed_data)-1)]['High']/$split_adj))) {
        // And PSAR is lower than it should be.
        $parabolic_sar  = ($processed_data[(count($processed_data)-1)]['Low']/$split_adj);
        $ep             = 0;
        $sp             = $sp_init;
      }
    }
    elseif ($parabolic_sar < ($row['Low']/$split_adj) and $ep < ($row['High']/$split_adj)) {
      // Up-trend
      $ep = ($row['High']/$split_adj);

      // Check the accelleration factor; Increase it if necessary.
      if ($sp <= $sp_max) $sp = $sp + $sp_init;
    }
    elseif ($parabolic_sar > ($row['High']/$split_adj) and $ep > ($row['Low']/$split_adj)) {
      // Down-trend
      $ep = ($row['Low']/$split_adj);

      // Check the accelleration factor; Increase it if necessary.
      if ($sp <= $sp_max) $sp = $sp + $sp_init;
    }

    return array($parabolic_sar, $ep, $sp);
  }

  /**
   * Calculates the Simple Moving Average
   *
   * @access public
   * @param  array   $data
   * @param  integer $periods
   * @param  string  $label
   * @return float
   */
  public function sma($data, $periods, $label){
    if(is_array($data) and count($data) >= $periods){
      $sum = 0;
      for($a=0; $a<count($data); $a++){
        $sum += $data[$a][$label];
      }
      return number_format(($sum/$periods), 4, '.', '');
    }
    return 0;
  }

  /**
   * Calculates the standard deviation
   *
   * @access public
   * @param  float    $current_price
   * @param  float    $sma
   * @param  integer  $periods
   * @return float
   */
  public function standardDeviation($current_price, $sma, $periods)
  {
    $numerator = pow(($current_price-$sma),2);

    $base = $numerator/$periods;

    return abs(sqrt($base));
  }

  /**
   * Calculates the base stochastic average
   *
   * @access public
   * @param  array  $data
   * @param  float  $close
   * @param  float  $highest
   * @param  float  $lowest
   * @return float
   */
  public function stochastic($close, $highest, $lowest)
  {
    // Return null if the the divisor is zero (0)
    if ($highest-$lowest == 0) {
      return null;
    }

    // Return the stochastic value (((Recent Close - Lowest Low)/(Highest High - Lowest Low))*100)
    return (($close - $lowest)/($highest-$lowest))*100;
  }

  /**
   * Calculates the fast stochastic average
   *
   * @access public
   * @param  array    $data
   * @param  integer  $smoothing_factor
   * @param  string   $high_label
   * @param  string   $low_label
   * @param  string   $close_label
   * @return float
   */
  public function stochasticFast($data, $smoothing_factor, $high_label, $low_label, $close_label)
  {
    $highest_high = 0;
    $lowest_low = 0;

    // Loop over the periods to calculate against.
    for ($i=0; $i<count($data); $i++) {
      // Get the lowest low
      if (empty($lowest_low)) {
        $lowest_low = $data[$i][$low_label];
      }
      elseif ($lowest_low > $data[$i][$low_label]) {
        $lowest_low = $data[$i][$low_label];
      }

      // Get the highest high
      if (empty($highest_high)) {
         $highest_high = $data[$i][$high_label];
      }
      elseif ($highest_high < $data[$i][$high_label]) {
        $highest_high = $data[$i][$high_label];
      }
    }

    $data[count($data)-1]['stoFastK'] = number_format($this->stochastic($data[count($data)-1][$close_label],$highest_high,$lowest_low), 4, '.', '');
    if(count($data)<$smoothing_factor+1){
      $slice = array_slice($data, 0, count($data));
    }
    else{
      $slice = array_slice($data, (count($data))-$smoothing_factor, $smoothing_factor);
    }
    while(count($slice)<$smoothing_factor){
      array_unshift($slice, $slice[0]);
    }

    $data[count($data)-1]['stoFastD'] = $this->sma($slice, $smoothing_factor, 'stoFastK');

    // Return the stochastic value (((Recent Close - Lowest Low)/(Highest High - Lowest Low))*100)
    return $data[count($data)-1];
  }

  /**
   * Calculates the slow stochastic average
   *
   * @access public
   * @param  array    $data
   * @param  integer  $smoothing_factor
   * @param  string   $high_label
   * @param  string   $low_label
   * @param  string   $close_label
   * @return float
   */
  public function stochasticSlow($data, $smoothing_factor, $high_label, $low_label, $close_label)
  {
    $stochastic_fast = $this->stochasticFast($data, $smoothing_factor, $high_label, $low_label, $close_label);

    $data[count($data)-1]['stoSlowK'] = $stochastic_fast['stoFastD'];
    if(count($data)<$smoothing_factor+1){
      $slice = array_slice($data, 0, count($data));
    }
    else{
      $slice = array_slice($data, (count($data))-$smoothing_factor, $smoothing_factor);
    }
    while(count($slice)<$smoothing_factor){
      array_unshift($slice, $slice[0]);
    }
    $data[count($data)-1]['stoSlowD'] = $this->sma($slice, $smoothing_factor, 'stoSlowK');

    return $data[count($data)-1];
  }
}
?>
