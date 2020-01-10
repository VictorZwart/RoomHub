function _hide_element_on_check(hide_trigger, hide_item) {
    // actually hide
    $(hide_item).toggle(!$(hide_trigger).is(':checked'));
}

function hide_element_on_check(hide_trigger, hide_item) {
    // check once and setup onclick to trigger
    _hide_element_on_check(hide_trigger, hide_item);
    $(hide_trigger).on('click', e => _hide_element_on_check(e.target, hide_item))
}