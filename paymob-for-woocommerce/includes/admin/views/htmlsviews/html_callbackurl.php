<?php

return '<span id="cburl" class="button-secondary callback_copy">' . add_query_arg( array( 'wc-api' => 'paymob_callback' ), home_url() ) . '</span>';
