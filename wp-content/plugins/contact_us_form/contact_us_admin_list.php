<?php
class Contacts_List_Table extends WP_List_Table {
    var $items_per_page = 10;
    var $contacts_data = array();
    var $total_contacts_data = array();
    function __construct(){
    global $status, $page;
   if(isset($_REQUEST['action']) && $_REQUEST['action']!='' && isset($_REQUEST['id']) && $_REQUEST['id']!=''){
        if($_REQUEST['action']==='delete'){
            $this->delete_contact($_REQUEST['id']);
        }
       if($_REQUEST['action']==='edit'){
           if(isset($_REQUEST['save'])){
               $this->save_contact($_REQUEST['id']);
           }else{
               $this->edit_contact($_REQUEST['id']);
           }
       }
    }
        parent::__construct( array(
            'singular'  => __( 'contact', 'tauro' ),
            'plural'    => __( 'contacts', 'tauro' ),
            'ajax'      => false
    ) );
    }
    function column_default( $item, $column_name ) {
      switch( $column_name ) {
          case 'name':
          case 'phone':
          case 'email':
          case 'subject':
          case 'msg':
          case 'datetime':
          case 'status':
          case 'comment':
              return $item[ $column_name ];
          default:
              return print_r( $item, true ) ;
      }
  }
    function get_sortable_columns() {
        $sortable_columns = array(
            'name'  => array('name',false),
            'phone'   => array('phone',false),
            'email' => array('email',false),
            'subject'   => array('subject',false),
            'msg'   => array('msg',false),
            'datetime'   => array('datetime',false),
            'status'   => array('status',false),
            'comment'   => array('comment',false)
        );
        return $sortable_columns;
    }
    function get_columns(){
        $columns = array(
            'name' => __( 'Name', 'tauro' ),
            'phone'      => __( 'Phone', 'tauro' ),
            'email'    => __( 'Email', 'tauro' ),
            'subject'      => __( 'Theme', 'tauro' ),
            'msg'      => __( 'Message', 'tauro' ),
            'datetime'      => __( 'Form Sent Date', 'tauro' ),
            'status'      => __( 'Status', 'tauro' ),
            'comment'      => __( 'Comment', 'tauro' ),
        );
         return $columns;
    }
    function column_name($item) {
        $delete_nonce = wp_create_nonce( 'sp_delete_contact' );
        $edit_nonce = wp_create_nonce( 'sp_edit_contact' );
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">'.__( "Edit", "tauro" ).'</a>',$_REQUEST['page'],'edit',$item['id'],$edit_nonce),
            'delete'    => sprintf('<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">'.__( "Delete", "tauro" ).'</a>',$_REQUEST['page'],'delete',$item['id'],$delete_nonce),
        );

        return sprintf('%1$s %2$s', $item['name'], $this->row_actions($actions) );
    }
    function save_contact($id){
    global $wpdb;
    $nonce = esc_attr( $_REQUEST['_wpnonce'] );
    if ( ! wp_verify_nonce( $nonce, 'sp_edit_contact' ) ) {
        die( __( "Go get a life script kiddies", "tauro" ));
    }
    else {
        $wpdb->update(
            "{$wpdb->prefix}contacts",
            ['comment' => $wpdb->_escape($_REQUEST['comment']),'status' => __( "seen", "tauro" )],
            ['id' => $wpdb->_escape($id)]
        );

        wp_redirect( esc_url_raw( add_query_arg( 'msg',__( "Contact Updated Successfully", "tauro" ) ,remove_query_arg( array('action', 'id', '_wpnonce'), $_SERVER['REQUEST_URI'] ) )) );

    }
}
    function edit_contact( $id ) {
        global $wpdb;
        $nonce = esc_attr( $_REQUEST['_wpnonce'] );
        if ( ! wp_verify_nonce( $nonce, 'sp_edit_contact' ) ) {
            die( __( "Go get a life script kiddies", "tauro" ));
        }
        else {
            $query = "SELECT * FROM {$wpdb->prefix}contacts WHERE id=".$wpdb->_escape($id);
            $contact_details = $wpdb->get_row($query);
            if($contact_details){
                echo '<h2>'.__('Contact Details', 'tauro') .'</h2><form method="post">
                        <table>
                            <tr><td>'.__('Name', 'tauro') .'</td><td>'.$contact_details->name.'</td></tr>
                            <tr><td>'.__('Phone', 'tauro') .'</td><td>'.$contact_details->phone.'</td></tr>
                            <tr><td>'.__('Email', 'tauro') .'</td><td>'.$contact_details->email.'</td></tr>
                            <tr><td>'.__('Theme', 'tauro') .'</td><td>'.$contact_details->subject.'</td></tr>
                            <tr><td>'.__('Message', 'tauro') .'</td><td>'.$contact_details->msg.'</td></tr>
                            <tr><td>'.__('Form Sent Date', 'tauro') .'</td><td>'.$contact_details->datetime.'</td></tr>
                            <tr><td>'.__('Status', 'tauro') .'</td><td>'.$contact_details->status.'</td></tr>
                            <tr><td>'.__('Comment', 'tauro') .'</td><td><textarea name="comment">'.$contact_details->comment.'</textarea></td></tr>
                            <tr><td></td><td><input type="submit" name="save" value="'.__('Save', 'tauro').'"></td></tr>
                         </table>
                       </form>';
            }

        }
    }
    function delete_contact( $id ) {
        global $wpdb;
        $nonce = esc_attr( $_REQUEST['_wpnonce'] );
        if ( ! wp_verify_nonce( $nonce, 'sp_delete_contact' ) ) {
            die( __( "Go get a life script kiddies", "tauro" ));
        }
        else {
            $wpdb->delete(
                "{$wpdb->prefix}contacts",
                ['id' => $wpdb->_escape($id)],
                ['%d']
            );

            wp_redirect( esc_url_raw( add_query_arg( 'msg',__( "Contact Deleted Successfully", "tauro" ) ,remove_query_arg( array('action', 'id', '_wpnonce'), $_SERVER['REQUEST_URI'] ) )) );
        }

    }
    function usort_reorder( $a, $b ) {
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'status';
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
        $result = strcmp( $a[$orderby], $b[$orderby] );
        return ( $order === 'asc' ) ? $result : -$result;
    }
    function prepare_items() {
    global $wpdb;

    $search = ($_GET['s'])?$wpdb->_escape($_GET['s']):'';
    $search_query='';
    if(!empty($search)){ $search_query =' WHERE name like "%'.$search.'%" OR subject like "%'.$search.'%" OR email like "%'.$search.'%" OR msg like "%'.$search.'%"  OR phone like "%'.$search.'%"';};

    $query = "SELECT * FROM {$wpdb->prefix}contacts ".$search_query;
    $query_count = "SELECT count(*) FROM {$wpdb->prefix}contacts ".$search_query;

    $orderby = !empty($_GET["orderby"]) ? $wpdb->_escape($_GET["orderby"]) : 'datetime';
    $order = !empty($_GET["order"]) ? $wpdb->_escape($_GET["order"]) : 'desc';
    if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }
    $paged = !empty($_GET["paged"]) ? $wpdb->_escape($_GET["paged"]) : '';

    if(empty($paged) || !is_numeric($paged) || $paged<=0 ){
        $paged=1;
    }
    $this->total_contacts_data=$wpdb->get_var($query_count);

    if(!empty($paged) && !empty($this->items_per_page)){
        $offset=($paged-1)*$this->items_per_page;
        $query.=' LIMIT '.(int)$offset.','.(int)$this->items_per_page;
    }
    $this->contacts_data=$wpdb->get_results($query, ARRAY_A);

  $columns  = $this->get_columns();
  $hidden   = array();
  $sortable = $this->get_sortable_columns();
  $this->_column_headers = array( $columns, $hidden, $sortable );

  usort( $this->contacts_data, array( &$this, 'usort_reorder' ) );
    $this->set_pagination_args( array(
        'total_items' => $this->total_contacts_data,
        'per_page'    => $this->items_per_page
    ) );
  $this->items = $this->contacts_data;

}
    function no_items() {
        _e( 'No Contacts Found',"tauro" );
    }
    public function display() {
        $singular = $this->_args['singular'];
        $this->screen->render_screen_reader_content( 'heading_list' );
        ?>
        <div class="wrap"><h2><?php _e("Contacts", "tauro");?></h2>
            <h4><?php echo ($_REQUEST['msg'])?$_REQUEST['msg']:'';?></h4>
        <form method="GET">
            <input type="hidden" name="page" value="contacts_list" />
            <p class="search-box">
                <label class="screen-reader-text" for="search_id-search-input"><?php _e("Search", "tauro");?>:</label>
                <input id="search_id-search-input" type="text" name="s" value="<?php  echo (($_GET['s'])?$_GET['s']:'')?>" />
                <input id="search-submit" class="button" type="submit" name="" value="<?php  _e("Search", "tauro");?>" />
            </p>
        </form><br><br>
        <form id="contacts_list" method="get">
        <table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
            <thead>
            <tr>
                <?php $this->print_column_headers(); ?>
            </tr>
            </thead>

            <tbody id="the-list"
                <?php
                if ( $singular ) {
                    echo " data-wp-lists='list:$singular'";
                }
                ?>
            >
            <?php $this->display_rows_or_placeholder(); ?>
            </tbody>

            <tfoot>
            <tr>
                <?php $this->print_column_headers( false ); ?>
            </tr>
            </tfoot>

        </table>
        </form>
        </div>
        <?php
        $this->display_tablenav( 'bottom' );
    }
}


function contact_add_menu_items(){
    add_menu_page( __('Contacts', 'tauro'), __('Contacts', 'tauro'), 'activate_plugins', 'contacts_list', 'contact_render_list_page');
}
add_action( 'admin_menu', 'contact_add_menu_items' );
function contact_render_list_page(){
  $myListTable = new Contacts_List_Table();
  $myListTable->prepare_items();
  $myListTable->display();
}
