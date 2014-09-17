// Configure io_bb if required
var io_bbout_element_id = 'io_bb';  // set this to the ID of your hidden field
var io_enable_rip = true;
var io_install_flash = false;
var io_install_stm = false;
var io_exclude_stm = 12;

var io_element = document.createElement('input');
io_element.id = 'io_bb';
io_element.name = 'io_bb';
io_element.type = 'hidden';
document.getElementsByName('checkout')[0].appendChild(io_element);