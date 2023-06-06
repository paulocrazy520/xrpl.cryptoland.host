
<input type="hidden" id="cardsCount" name="cardsCount" value='0' />
<input type="hidden" id="pageType" name="pageType" value='claim' />
<input type="hidden" id="tabType" name="tabType" value='*' />

<input type="hidden" id="numCardsPerPage" value="<?php echo $num_results_on_page; ?>"/>

<div class="cs-isotop_filter cs-style1 cs-center">
      <ul class="cs-mp0 cs-center">
        <li  class="active"><a href="#" data-filter="*"><span>All</span></a></li>
        <li><a href="#" data-filter=".unclaimed"><span>UnClaimed </span></a></li>
        <li><a href="#" data-filter=".unrevealed"><span>Claimed/UnRevealed</span></a></li>
        <li><a href="#" data-filter=".revealed"><span>Revealed </span></a></li>
      </ul>
</div>

<div class="cs-height_30 cs-height_lg_30"></div>