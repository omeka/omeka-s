<?php
/**
 * ARC2 Store Dumper.
 *
 * @author Benjamin Nowack
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 *
 * @version 2010-11-16
 */
ARC2::inc('Class');

class ARC2_StoreDumper extends ARC2_Class
{
    public int $keep_time_limit;
    public int $limit;
    public $store;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->store = $this->caller;
        $this->keep_time_limit = $this->v('keep_time_limit', 0, $this->a);
        $this->limit = 100000;
    }

    public function dumpSPOG()
    {
        header('Content-Type: application/sparql-results+xml');
        if ($this->v('store_use_dump_dir', 0, $this->a)) {
            $path = $this->v('store_dump_dir', 'dumps', $this->a);
            /* default: monthly dumps */
            $path_suffix = $this->v('store_dump_suffix', date('Y_m'), $this->a);
            $path .= '/dump_'.$path_suffix.'.spog';
            if (!file_exists($path)) {
                $this->saveSPOG($path);
            }
            readfile($path);
            exit;
        }
        echo $this->getHeader();
        $offset = 0;
        do {
            $proceed = 0;
            $rows = $this->getRecordset($offset);
            if (false == is_array($rows)) {
                break;
            }
            foreach ($rows as $row) {
                echo $this->getEntry($row);
                $proceed = 1;
            }
            $offset += $this->limit;
        } while ($proceed);
        echo $this->getFooter();
    }

    public function saveSPOG($path, $q = '')
    {
        if ($q) {
            return $this->saveCustomSPOG($path, $q);
        }
        if (!$fp = fopen($path, 'w')) {
            return $this->addError('Could not create backup file at '.realpath($path));
        }
        fwrite($fp, $this->getHeader());
        $offset = 0;
        do {
            $proceed = 0;
            $rows = $this->getRecordset($offset);
            if (false == is_array($rows)) {
                break;
            }
            foreach ($rows as $row) {
                fwrite($fp, $this->getEntry($row));
                $proceed = 1;
            }
            $offset += $this->limit;
        } while ($proceed);
        fwrite($fp, $this->getFooter());
        fclose($fp);

        return 1;
    }

    public function saveCustomSPOG($path, $q)
    {
        if (!$fp = fopen($path, 'w')) {
            return $this->addError('Could not create backup file at '.realpath($path));
        }
        fwrite($fp, $this->getHeader());
        $rows = $this->store->query($q, 'rows');
        foreach ($rows as $row) {
            fwrite($fp, $this->getEntry($row));
        }
        fwrite($fp, $this->getFooter());
        fclose($fp);
    }

    public function getRecordset($offset)
    {
        $prefix = $this->store->getTablePrefix();
        $sql = '
      SELECT
        VS.val AS s,
        T.s_type AS `s type`,
        VP.val AS p,
        0 AS `p type`,
        VO.val AS o,
        T.o_type AS `o type`,
        VLDT.val as `o lang_dt`,
        VG.val as g,
        0 AS `g type`
      FROM
        '.$prefix.'triple T
        JOIN '.$prefix.'s2val VS ON (T.s = VS.id)
        JOIN '.$prefix.'id2val VP ON (T.p = VP.id)
        JOIN '.$prefix.'o2val VO ON (T.o = VO.id)
        JOIN '.$prefix.'id2val VLDT ON (T.o_lang_dt = VLDT.id)
        JOIN '.$prefix.'g2t G2T ON (T.t = G2T.t)
        JOIN '.$prefix.'id2val VG ON (G2T.g = VG.id)
    ';
        if ($this->limit) {
            $sql .= ' LIMIT '.$this->limit;
        }
        if ($offset) {
            $sql .= ' OFFSET '.$offset;
        }

        $rows = $this->store->a['db_object']->fetchList($sql);
        if (false == empty($this->store->a['db_object']->getErrorMessage())) {
            return $this->addError($this->store->a['db_object']->getErrorMessage());
        }

        return $rows;
    }

    public function getHeader()
    {
        $n = "\n";

        return ''.
      '<?xml version="1.0"?>'.
      $n.'<sparql xmlns="http://www.w3.org/2005/sparql-results#">'.
      $n.'  <head>'.
      $n.'    <variable name="s"/>'.
      $n.'    <variable name="p"/>'.
      $n.'    <variable name="o"/>'.
      $n.'    <variable name="g"/>'.
      $n.'  </head>'.
      $n.'  <results>'.
    '';
    }

    public function getEntry($row)
    {
        if (!$this->keep_time_limit) {
            set_time_limit($this->v('time_limit', 1200, $this->a));
        }
        $n = "\n";
        $r = '';
        $r .= $n.'    <result>';
        foreach (['s', 'p', 'o', 'g'] as $var) {
            if (isset($row[$var])) {
                $type = (string) $row[$var.' type'];
                $r .= $n.'      <binding name="'.$var.'">';
                $val = $this->toUTF8($row[$var]);
                if (('0' == $type) || ('uri' == $type)) {
                    $r .= $n.'        <uri>'.$this->getSafeValue($val).'</uri>';
                } elseif (('1' == $type) || ('bnode' == $type)) {
                    $r .= $n.'        <bnode>'.substr($val, 2).'</bnode>';
                } else {
                    $lang_dt = '';
                    foreach (['o lang_dt', 'o lang', 'o datatype'] as $k) {
                        if (('o' == $var) && isset($row[$k]) && $row[$k]) {
                            $lang_dt = $row[$k];
                        }
                    }
                    $is_lang = preg_match('/^([a-z]+(\-[a-z0-9]+)*)$/i', $lang_dt);
                    list($lang, $dt) = $is_lang ? [$lang_dt, ''] : ['', $lang_dt];
                    $lang = $lang ? ' xml:lang="'.$lang.'"' : '';
                    $dt = $dt ? ' datatype="'.htmlspecialchars($dt).'"' : '';
                    $r .= $n.'        <literal'.$dt.$lang.'>'.$this->getSafeValue($val).'</literal>';
                }
                $r .= $n.'      </binding>';
            }
        }
        $r .= $n.'    </result>';

        return $r;
    }

    public function getSafeValue($val)
    {/* mainly for fixing json_decode bugs */
        $mappings = [
            '%00' => '',
            '%01' => '',
            '%02' => '',
            '%03' => '',
            '%04' => '',
            '%05' => '',
            '%06' => '',
            '%07' => '',
            '%08' => '',
            '%09' => '',
            '%0B' => '',
            '%0C' => '',
            '%0E' => '',
            '%0F' => '',
            '%15' => '',
            '%17' => 'ė',
            '%1A' => ',',
            '%1F' => '',
        ];
        $froms = array_keys($mappings);
        $tos = array_values($mappings);
        foreach ($froms as $i => $from) {
            $froms[$i] = urldecode($from);
        }
        $val = str_replace($froms, $tos, $val);
        if (str_contains($val, '</')) {
            $val = "\n<![CDATA[\n".$val."\n]]>\n";
        } else {
            $val = htmlspecialchars($val);
        }

        return $val;
    }

    public function getFooter()
    {
        $n = "\n";

        return ''.
      $n.'  </results>'.
      $n.'</sparql>'.
      $n.
    '';
    }
}
