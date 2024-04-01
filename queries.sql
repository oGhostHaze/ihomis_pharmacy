SELECT  [pharm_drug_stocks].[dmdcomb]
       ,[pharm_drug_stocks].[dmdctr]
       ,[drug_concat]
       ,[hcharge].[chrgdesc]
       ,[pharm_drug_stocks].[chrgcode]
       ,[hdmhdrprice].[retail_price]
       ,[dmselprice]
       ,[pharm_drug_stocks].[loc_code]
       ,[pharm_drug_stocks].[dmdprdte]
       ,SUM(stock_bal) AS stock_bal
       ,MAX(id)        AS id
       ,MIN(exp_date)  AS exp_date
FROM [hospital].[dbo].[pharm_drug_stocks]
INNER JOIN [hcharge]
ON [hcharge].[chrgcode] = [pharm_drug_stocks].[chrgcode]
INNER JOIN [hdmhdrprice]
ON [hdmhdrprice].[dmdprdte] = [pharm_drug_stocks].[dmdprdte]
WHERE [loc_code] = ?
GROUP BY  [pharm_drug_stocks].[dmdcomb]
         ,[pharm_drug_stocks].[dmdctr]
         ,[pharm_drug_stocks].[chrgcode]
         ,[hdmhdrprice].[retail_price]
         ,[dmselprice]
         ,[drug_concat]
         ,[hcharge].[chrgdesc]
         ,[pharm_drug_stocks].[loc_code]
         ,[pharm_drug_stocks].[dmdprdte];

INSERT INTO hospital.dbo.hrxo(docointkey, enccode, hpercode, rxooccid, rxoref, dmdcomb, repdayno1, rxostatus,
rxolock, rxoupsw, rxoconfd, dmdctr, estatus, entryby, ordcon, orderupd, locacode, orderfrom, issuetype,
has_tag, tx_type, ris, pchrgqty, pchrgup, pcchrgamt, dodate, dotime, dodtepost, dotmepost, dmdprdte, exp_date, loc_code, item_id, remarks )
VALUES ( :docointkey, :enccode, :hpercode, :rxooccid, :rxoref, :dmdcomb, :repdayno1, :rxostatus, :rxolock, :rxoupsw,
:rxoconfd, :dmdctr, :estatus, :entryby, :ordcon, :orderupd, :locacode, :orderfrom, :issuetype, :has_tag, :tx_type, :ris, :pchrgqty
:pchrgup , :pcchrgamt , :dodate , :dotime , :dodtepost , :dotmepost , :dmdprdte , :exp_date , :loc_code , :item_id , :remarks )
, [ 'docointkey' = > '0000040' . $this-> hpercode . date('m/d/Yh:i:s'
, strtotime(now())) . $chrgcode . $dmdcomb . $dmdctr
, 'enccode' = > $enccode
, 'hpercode' = > $this-> hpercode
, 'rxooccid' = > '1'
, 'rxoref' = > '1'
, 'dmdcomb' = > $dmdcomb
, 'repdayno1' = > '1'
, 'rxostatus' = > 'A'
, 'rxolock' = > 'N'
, 'rxoupsw' = > 'N'
, 'rxoconfd' = > 'N'
, 'dmdctr' = > $dmdctr
, 'estatus' = > 'U'
, 'entryby' = > session('employeeid')
, 'ordcon' = > 'NEWOR'
, 'orderupd' = > 'ACTIV'
, 'locacode' = > 'PHARM'
, 'orderfrom' = > $chrgcode
, 'issuetype' = > 'c'
, 'has_tag' = > $this-> type ? true : false
, //added 'tx_type' = > $this-> type
, //added 'ris' = > $this-> is_ris ? true : false
, 'pchrgqty' = > $this-> order_qty
, 'pchrgup' = > $this-> unit_price
, 'pcchrgamt' = > $this-> order_qty * $this-> unit_price
, 'dodate' = > now()
, 'dotime' = > now()
, 'dodtepost' = > now()
, 'dotmepost' = > now()
, 'dmdprdte' = > $dmdprdte
, 'exp_date' = > $exp_date
, //added 'loc_code' = > $loc_code
, //added 'item_id' = > $id
, //added 'remarks' = > $this-> remarks
, //added ]
