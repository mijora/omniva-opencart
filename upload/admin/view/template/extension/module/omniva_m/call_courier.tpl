<div class="form-horizontal">
    <div class="form-group">
        <label class="col-sm-2 control-label"><?php echo $omniva_m_cc_available_time; ?></label>
        <div class="col-sm-10">
            <div class="input-group" style="display: flex; align-items: center; justify-content: center; gap: 1em;">
                <select name="omniva_m_cc_start" class="form-control">
                    <?php 
                    foreach ($timeRangeStart as $hour) {
                        echo '<option value="' . $hour . '" ' . ($timeHourStart == $hour ? 'selected' : '') . '>' . $hour . '</option>';
                    }
                    ?>
                </select>
                <span>-</span>
                <select name="omniva_m_cc_end" class="form-control">
                    <?php 
                    foreach ($timeRangeEnd as $hour) {
                        echo '<option value="' . $hour . '" ' . ($timeHourEnd == $hour ? 'selected' : '') . '>' . $hour . '</option>';
                    }
                    ?>
                </select>
            </div>
            <p class="help-block"><?php echo $omniva_m_cc_help_available_time; ?></p>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label"><?php echo $omniva_m_cc_parcels; ?></label>
        <div class="col-sm-10">
            <div class="input-group">
                <input type="text" name="omniva_m_cc_parcels" value="1" class="form-control" />
            </div>
            <p class="help-block"><?php echo $omniva_m_cc_help_parcels; ?></p>
        </div>
    </div>
</div>

<?php if ($callTimes): ?>
<div class="alert alert-info">
    <label><?php echo $omniva_m_cc_label_called_times; ?></label>
    <section><?php echo $callTimes; ?></section>
</div>
<?php endif; ?>