<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

  define('CONST_POS_MIDDLE', 'middle');
  define('CONST_POS_FIRST', 'first');
  define('CONST_POS_LAST', 'last');
  
class Flx_Model extends CI_Model
{ 

  function __construct() {
      parent::__construct();
      
      $this->config->load('flx_db_errors');
      $this->lang->load('flx_db/errors', $this->config->item('language'));
  }
  
  public function check_error ($q_result, $method_name)
  {
    if (empty($q_result)) return false; // Get out if input is empty
    if (empty($method_name)) $method_name = 'flx_db_common';
    
    $errors_arr = $this->config->item($method_name);
    if (array_key_exists($q_result, $errors_arr)) return false; // Found error code
    
    return true;
  }
  
  public function get_error_text ($err_code, $method_name)
  {
    if (empty($err_code)) return false; // Get out if input is empty
    if (empty($method_name)) $method_name = 'flx_db_common';
    
    $errors_arr = $this->config->item($method_name);
    if (array_key_exists($err_code, $errors_arr)) return $this->lang->line($errors_arr[$err_code]);
    
    return false;
  }

  private function get_table (&$user, $table, $where = '', $order = '', $limit = '')
  {
    $sql = 'CALL get_table(?,?,?,?,?,?)';

    //var_export(array($user->user_token, $user->user_ip, $table, $where, $order, $limit)); echo('<br/>');
    
    return $this->db->query($sql, array($user->user_token, $user->user_ip, $table, $where, $order, $limit));
    
//     $result = $this->db->query($sql, array($user->user_token, $user->user_ip, $table, $where, $order, $limit));
//     var_export($result->row_array());echo('<br/>');
//     while($this->db->conn_id->next_result())
//     {
//       $result = $this->db->conn_id->store_result();
//       var_export(mysqli_num_rows($result));echo('<br/>');
//       if (is_object($result)){ var_export($result->fetch_array()); echo('<br/>'); mysqli_free_result ($result);}
//       else { echo('TERMINATOR? <br/>');}
//     }
  }
  
  // Возвращает многомерный ассоциативный массив - поля запрошенной строки, с указанием типа и заголовка
  protected function row_array (&$user, $table, $where = '')
  {
    // Ну тут всё понятно - вызываем метод get_table, выставив лимит = 1
    // Метод возвращает сразу объект типа CI_DB_mysqli_result
    $first_result = $this->get_table ($user, $table, $where, '', 1);
    
    if (!empty($first_result) && $first_result->num_rows() == 1)
    {
      $final_result = array();
      // Получаем результат запроса стандартным методом CI
      $first_result = $first_result->row_array();
      
      if (empty($first_result['columns_defs'])) return false;
      
      $first_result = json_decode($first_result['columns_defs'] , true);
      
      // Формируем массив определений для полей результата (первый результат запроса)
      foreach ($first_result as $key=>$value)
      {
        $final_result['type'][$key] = $value['type'];
        $final_result['caption'][$key] = $value['caption'];
      }
      
      // А теперь самое интересное - начинаем работать напрямую с методами mysqli
      // Сначала получим второй результат запроса, в качестве объекта типа mysqli - $this->db->conn_id
      if ($this->db->conn_id->next_result())
      {
        $second_result = $this->db->conn_id->store_result();
        
        // Если полученый результат - объект и в результате больше одной строки
        if (is_object($second_result) && mysqli_num_rows($second_result) > 0)
        {
          $second_result = mysqli_fetch_assoc($second_result);
          // Дозаполним результирующий массив значениями
          foreach ($second_result as $key=>$value)
            {
              $final_result['value'][$key] = $value;
            }
        }
        else 
        {
          $this->_clear_results();
          return false;
        }
        
        $this->_clear_results();
        
        return $final_result;
      }
      else return false;
    }
    else return false;
  }
  
  protected function full_array (&$user, $table, $where = '', $order = '', $limit = '')
  {
    // Вызываем метод get_table.
    // Метод возвращает сразу объект типа CI_DB_mysqli_result
    $first_result = $this->get_table ($user, $table, $where, $order, $limit);
    
    if (!empty($first_result) && $first_result->num_rows() == 1)
    {
      $final_result = array();
      // Создадим отдельный массив определений, будем добавлять его значения к каждой строке результата
      $defs_array = array();
      // Получаем результат запроса стандартным методом CI
      $first_result = $first_result->row_array();
      
      if (empty($first_result) || empty($first_result['columns_defs'])) return false;
      
      $first_result = json_decode($first_result['columns_defs'] , true);
      
      // Формируем массив определений для полей результата (первый результат запроса)
      foreach ($first_result as $key=>$value)
      {
        $final_result['type'][$key] = $value['type'];
        $final_result['caption'][$key] = $value['caption'];
      }
      
      // А теперь самое интересное - начинаем работать напрямую с методами mysqli
      // Сначала получим второй результат запроса, в качестве объекта типа mysqli - $this->db->conn_id
      if ($this->db->conn_id->next_result())
      {
        $second_result = $this->db->conn_id->store_result();
        $row_result = array();
        // Если полученый результат - объект и в результате больше одной строки
        if (is_object($second_result) && mysqli_num_rows($second_result) > 0)
        {
          $final_result['values'] = array();
          // Теперь пройдёмся по всем строкам результата запроса
          $array_counter = 0;
          while ($row_result = mysqli_fetch_assoc($second_result))
          {
            $row_result['position'] = CONST_POS_MIDDLE;
            if ($array_counter == 0) $row_result['position'] = CONST_POS_FIRST;
            $array_counter = array_push ($final_result['values'],$row_result);
          }
          $final_result['values'][$array_counter-1]['position'] = CONST_POS_LAST;
          unset($row_result);
        }
        else 
        {
          $this->_clear_results();
          return false;
        }
        
        // Получим третий результат - количество строк в таблице
        if ($this->db->conn_id->next_result())
        {
          $third_result = $this->db->conn_id->store_result();
          
          // Если полученый результат - объект и в результате больше одной строки
          if (is_object($third_result) && mysqli_num_rows($third_result) > 0)
          {
            $third_result = mysqli_fetch_assoc($third_result);
            $final_result['total_count'] = $third_result['total_count'];
          }
        }
        
        $this->_clear_results();
        
        return $final_result;
      }
      else return false;
    }
    else return false;
  }
  
  protected function row_object ($user, $table, $where = '')
  {
    // Вызываем метод get_table.
    // Метод возвращает объект типа CI_DB_mysqli_result
    $first_result = $this->get_table ($user, $table, $where, '', 1);
    
    if (!empty($first_result) && $first_result->num_rows() == 1)
    {
      $final_result = array();
      // Получаем результат запроса стандартным методом CI
      $first_result = $first_result->row_array();
      
      if (empty($first_result['columns_defs'])) return false;
      
      $first_result = json_decode($first_result['columns_defs'] , true);
      
      // Формируем массив определений для полей результата (первый результат запроса)
      foreach ($first_result as $key=>$value)
      {
        $final_result['type'][$key] = $value['type'];
        $final_result['caption'][$key] = $value['caption'];
      }
      
      // Преобразуем в объект
      $final_result['type'] = (object)$final_result['type'];
      $final_result['caption'] = (object)$final_result['caption'];
      
      // А теперь самое интересное - начинаем работать напрямую с методами mysqli
      // Сначала получим второй результат запроса, в качестве объекта типа mysqli - $this->db->conn_id
      if ($this->db->conn_id->next_result())
      {
        $second_result = $this->db->conn_id->store_result();
        
        // Если полученый результат - объект и в результате больше одной строки
        if (is_object($second_result) && mysqli_num_rows($second_result) > 0)
        {
          $second_result = mysqli_fetch_assoc($second_result);
          // Дозаполним результирующий массив значениями
          foreach ($second_result as $key=>$value)
            {
              $final_result['value'][$key] = $value;
            }
          // Преобразуем в объект
          $final_result['value'] = (object)$final_result['value'];
        }
        else 
        {
          $this->_clear_results();
          return false;
        }
        
        $this->_clear_results();
        
        return (object)$final_result;
      }
      else return false;
    }
    else return false;
  }
  
  protected function full_objects (&$user, $table, $where = '', $order = '', $limit = '')
  {
    // Вызываем метод get_table.
    // Метод возвращает сразу объект типа CI_DB_mysqli_result
    $first_result = $this->get_table ($user, $table, $where, $order, $limit);
    
    if (!empty($first_result) && $first_result->num_rows() == 1)
    {
      $final_result = array();
      // Создадим отдельный массив определений, будем добавлять его значения к каждой строке результата
      $defs_array = array();
      // Получаем результат запроса стандартным методом CI
      $first_result = $first_result->row_array();
      
      if (empty($first_result['columns_defs'])) return false;
      
      $first_result = json_decode($first_result['columns_defs'] , true);
      
      // Формируем массив определений для полей результата (первый результат запроса)
      foreach ($first_result as $key=>$value)
      {
        $final_result['type'][$key] = $value['type'];
        $final_result['caption'][$key] = $value['caption'];
      }
      // Преобразуем в объект
      $final_result['type'] = (object)$final_result['type'];
      $final_result['caption'] = (object)$final_result['caption'];
      
      // А теперь самое интересное - начинаем работать напрямую с методами mysqli
      // Сначала получим второй результат запроса, в качестве объекта типа mysqli - $this->db->conn_id
      if ($this->db->conn_id->next_result())
      {
        $second_result = $this->db->conn_id->store_result();
        $row_result = array();
        // Если полученый результат - объект и в результате больше одной строки
        if (is_object($second_result) && mysqli_num_rows($second_result) > 0)
        {
          $final_result['values'] = array();
          // Теперь пройдёмся по всем строкам результата запроса
          $array_counter = 0;
          while ($row_result = mysqli_fetch_assoc($second_result))
          {
            $row_result['position'] = CONST_POS_MIDDLE;
            if ($array_counter == 0) $row_result['position'] = CONST_POS_FIRST;
            $array_counter = array_push ($final_result['values'], (object)$row_result);
          }
          $row_result = $final_result['values'][$array_counter-1];
          $row_result->position = CONST_POS_LAST;
          unset($row_result);
          
          // Получим третий результат - количество строк в таблице
          if ($this->db->conn_id->next_result())
          {
            $third_result = $this->db->conn_id->store_result();
            
            // Если полученый результат - объект и в результате больше одной строки
            if (is_object($third_result) && mysqli_num_rows($third_result) > 0)
            {
              $third_result = mysqli_fetch_assoc($third_result);
              $final_result['total_count'] = $third_result['total_count'];
            }
          }
        }
        else 
        {
          $this->_clear_results();
          return false;
        }
        
        $this->_clear_results();
        
        return (object)$final_result;
      }
      else return false;
    }
    else return false;
  }
  
  protected function _clear_results()
  {
    // Пройдёмся по всем результатам множественного запроса (на всякий случай)
    // и прочитаем их из буфера результатов, а заодно почистим.
    // Надо, чтобы не вываливалась ошибка “Command out of sync”
    while($this->db->conn_id->next_result())
    {
      $junk_result = $this->db->conn_id->store_result();
      if ($junk_result) mysqli_free_result ($junk_result);
    }
  }
  
} 
