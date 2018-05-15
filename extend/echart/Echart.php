<?php
/**
 * echart
 */

namespace echart;
/**
 * Class Echart
 */
class Echart{

    /**
     * 初始化
     */
    public function __construct()
    {


    }


    public function aa($pdo,$data = false)
    {
        $data_default = array(
            "legend" => "'海温距平','线性化趋势','低频滤波值'",
            "xAxis" => "'1997','1998','1999','2000','2001','2002','2003','2004','2005','2006','2007','2008','2009','2010','2011','2012','2013','2014','2015','2016'",
            "yAxis" => array(
            ),
            "series" => array(
                array( 'name' => '海温距平','type' => 'symbol_line_top','data' => "'0.0538558','0.323896','0.131661','0.0171606','0.171256','0.0643853','0.274381','0.242218','0.444207','0.425001','0.298802','0.370703','0.152229','0.500467','0.244916','0.374845','0.34475','0.292895','0.246639',''",'color' => "'#000000','#000000','#ff0000','#ff0000','#0000ff','#ff0000','#ff0000','#0000ff','#ff0000','#ff0000','#ff0000','#0000ff',"),
                array( 'name' => '线性化趋势','type' => 'bar','data' => "'0.151554','0.163804','0.176053','0.188303','0.200553','0.212803','0.225053','0.237303','0.249553','0.261803','0.274053','0.286303','0.298553','0.310803','0.323053','0.335303','0.347553','0.359803','0.372053','0.384303'",'color' => "'#000000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0FF0','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000',"),
                array( 'name' => '低频滤波值','type' => 'line',

                    'data' => "'0.200652','0.198248','0.15279','0.090166','0.100324','0.150511','0.208561','0.305861','0.384085','0.39592','0.352646','0.291778','0.306264','0.336354','0.349691','0.33135','0.338835','0.294416','',''",

                    'color' => "'#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000',"),
            ),
        );

        if(!$data)
        {
            $data = $data_default;
        }
        return $this->special_style($pdo,$data);
    }

    /**
     * $style（样式）  通用版
     *
     * @param string $div_id
     * @param $data
     * @param string $unit
     * @param string $theme
     * @return string            $scan_min y坐标最小值
     */
    public function common_style($div_id = "pdo",$data,$theme = "智慧池图形展示",$unit = "单位: °C",$scan_min = false)
    {
        $script = "
        <script>
        var myChart = echarts.init(document.getElementById('".$div_id."'));
        option = {
        title : {
        text: '".$theme."',          //标题
        x: 'center',
        y:'top',
    },
    tooltip: {
        trigger: 'axis'
    },
    legend: {
        data:[".$data['legend']."],
        itemWidth: 20,             // 图例图形宽度
        itemHeight: 14,            // 图例图形高度
        x: '65%', // 'center' | 'left' | {number},
        y: '5%', // 'center' | 'bottom' | {number}
        backgroundColor: '#eee',
        borderColor: 'rgba(178,34,34,0.8)',
//        orient: 'vertical',                   //竖直的，默认是水平
    },
    toolbox: {
        show : true,
        feature : {
            mark : {show: true},
            dataView : {show: true, readOnly: false},
            restore : {show: true},
            saveAsImage : {show: true}
        }
    },
    xAxis: [
        {
            type: 'category',
            data: [".$data['xAxis']."]
        }
    ],
    //x轴 和 y轴样式
    axisLabel:{
      show:true,
      textStyle:{
            fontSize:'8px',
            color:'blue',
            align:'center'
      },formatter:function(e){
         return e.substring(0);    //字符串截取
      }
    },
    yAxis: [
        {
            type: 'value',
            name: '".$unit."',
            x: 'left', // 'center' | 'left' | {number},
            y: 'top', // 'center' | 'bottom' | {number}
";

        //后加的，y坐标最小值
        if($scan_min)
        {
            $script.="min: ".$scan_min.",";
        }

        $script.="
            axisLabel: {
                formatter: '{value}'
            }
        }
    ],
    series: [
    ";
        foreach($data['series'] as $rows)
        {
            if($rows['type'] == "line")
            {
                $script.=$this->line_unit($rows);
                continue;
            }
            if($rows['type'] == "dotted")
            {
                $script.=$this->dotted_unit($rows);
                continue;
            }
            if($rows['type'] == "symbol_line")
            {
                $script.=$this->symbol_line_unit($rows,false);
                continue;
            }
            //如果是带顶部数值标识
            if($rows['type'] == "symbol_line_top")
            {
                $script.=$this->symbol_line_unit($rows,false,true);
                continue;
            }

            if($rows['type'] == "area_line")
            {
                $script.= $this->line_unit_with_area($rows,"red");
                continue;
            }

            if($rows['type'] == "bar_top")
            {
                $script.= $this->bar_unit($rows,false,true);
                continue;
            }

            $script.=$this->bar_unit($rows);
        };

        $script.="]}
        myChart.setOption(option);
        </script>";
        return $script;
    }

    /**
     * 柱形图的单元
     *
     * @param $rows
     * @param bool|false $param        是否有多个比例尺，是改值便为值
     * @param bool|false $top          顶部是否加标识
     * @return string
     */
    private function bar_unit($rows,$param = false,$top = false)
    {
        $script ="
            {
            name:'".$rows['name']."',
            type:'bar',
            data:[".$rows['data']."],
            itemStyle:{
                normal: {
                    color: function (params) {  var colorList = [ ".$rows['color']."]; return colorList[params.dataIndex]},
                    }},    //设置颜色
            ";
        if($param)
        {
            $script.="yAxisIndex: ".$param.",";
        }

        //图形顶部是否有数值标识
        if($top)
        {
            $script.="
        label: {
                normal: {
                    show: true,
                    position: 'top'
                }
            },";
        }

        $script.="
            barCategoryGap:'70%',        //柱形宽度比
            //symbol: 'none',           //去掉圈圈，若不要这一行，则圈圈出现
            //smooth:true,              //折线变平滑
            },";

        return $script;
    }

    /**
     * 折线单元
     *
     * @param $rows
     * @param bool|false $param          是否有多个别列尺，是改值便为值
     * @return string
     */
    private function line_unit($rows,$param = false)
    {
        $script ="
            {
            name:'".$rows['name']."',
            type:'".$rows['type']."',
            data:[".$rows['data']."],

            symbol: 'none',  //去掉圈圈，若不要这一行，则圈圈出现
            smooth:true,    //折线变平滑
             ";
        if($param)
        {
            $script.="yAxisIndex: ".$param.",";
        }
        $script.="
            itemStyle:{normal:{color:'".substr($rows['color'],1,7)."'}},    //设置颜色
            //itemStyle:{normal:{color:'#ff0000'}},    //设置颜色
            },";
        return $script;
    }


    /**
     * 折线单元(带面积的）
     *
     * @param $rows
     * @param string $area_color
     * @param bool|false $param                    如果存在就是对应的y轴坐标轴
     * @return string
     */
    private function line_unit_with_area($rows,$area_color = "red",$param = false)
    {
        $script ="
            {
            name:'".$rows['name']."',
            type:'".substr($rows['type'],5)."',
            data:[".$rows['data']."],
            symbol: 'none',  //去掉圈圈，若不要这一行，则圈圈出现
            smooth:true,    //折线变平滑
             ";
        if($param)
        {
            $script.="yAxisIndex: ".$param.",";
        }
        $script.="
            itemStyle:{normal:{color:'".substr($rows['color'],0,7)."'}},    //设置颜色
            areaStyle: {normal: {type: 'solid',color:'".$area_color."',opacity :'0.5'}},     //设置面积即阴影样式
            },";
        return $script;
    }


    /**
     * 虚线单元
     *
     * @param $rows
     * @param bool|false $param
     * @return string
     */
    private function dotted_unit($rows,$param = false)
    {
        $script ="
            {
            name:'".$rows['name']."',
            type:'line',
            data:[".$rows['data']."],

            symbol: 'none',  //去掉圈圈，若不要这一行，则圈圈出现
            smooth:true,    //折线变平滑
            ";
        if($param)
        {
            $script.="yAxisIndex: ".$param.",";
        }
        $script.="

            lineStyle:{normal:{type:'dotted',}},
            //itemStyle:{normal:{color:'".substr($rows['color'],1,7)."'}},    //设置颜色
            },";
        return $script;
    }


    /**
     * 折线单元 (带圈圈的 ，symbol)
     *
     * @param $rows
     * @param bool|false $param          是否有多个别列尺，是改值便为值
     * @return string
     */
    private function symbol_line_unit($rows,$param = false,$top = false)
    {
        $script ="
            {
            name:'".$rows['name']."',
            type:'line',
            data:[".$rows['data']."],
            showAllSymbol: true,                //折线出圈圈，全部显示
            symbolSize:6,                      //圈圈大小
             ";
        if($param)
        {
            $script.="yAxisIndex: ".$param.",";
        }

        //图形顶部是否有数值标识
        if($top)
        {
            $script.="
        label: {
                normal: {
                    show: true,
                    position: 'top'
                }
            },";
        }

        $script.="
            itemStyle:{normal:{color:'".substr($rows['color'],1,7)."'}},    //设置颜色
            //itemStyle:{normal:{color:'#ff0000'}},    //设置颜色
            },";
        return $script;
    }



    public function more_bzt_echart($div_id,$data)
    {
        $script = "
        <script >
       var myChart = echarts.init(document.getElementById('".$div_id."'));
       option = {
    tooltip: {
        trigger: 'item',
        formatter: ";
//        $script .="'{a}{b}:{c}({d}%)'";
        $script .= "'{b|{b}：}{c}{per|{d}%}',";
        $script.="
    },
    legend: {
        orient: 'vertical',
        x: 'left',
        data:['直达','营销广告','搜索引擎','邮件营销','联盟广告','视频广告','百度','谷歌','必应','其他']
    },
    series: [
        {
            name:'访问来源',
            type:'pie',
            selectedMode: 'single',
            radius: [0, '30%'],

            label: {
                normal: {
                    position: 'inner'
                }
            },
            labelLine: {
                normal: {
                    show: false
                }
            },
            data:[
                {value:335, name:'直达', selected:true},
                {value:679, name:'营销广告'},
                {value:1548, name:'搜索引擎'}
            ]
        },
        {
            name:'访问来源',
            type:'pie',
            radius: ['40%', '55%'],
            label: {
                normal: {
                    formatter: '{a|{a}}{abg|}\n{hr|}\n  {b|{b}：}{c}  {per|{d}%}  ',
                    backgroundColor: '#eee',
                    borderColor: '#aaa',
                    borderWidth: 1,
                    borderRadius: 4,
                    // shadowBlur:3,
                    // shadowOffsetX: 2,
                    // shadowOffsetY: 2,
                    // shadowColor: '#999',
                    // padding: [0, 7],
                    rich: {
                        a: {
                            color: '#999',
                            lineHeight: 22,
                            align: 'center'
                        },
                        // abg: {
                        //     backgroundColor: '#333',
                        //     width: '100%',
                        //     align: 'right',
                        //     height: 22,
                        //     borderRadius: [4, 4, 0, 0]
                        // },
                        hr: {
                            borderColor: '#aaa',
                            width: '100%',
                            borderWidth: 0.5,
                            height: 0
                        },
                        b: {
                            fontSize: 16,
                            lineHeight: 33
                        },
                        per: {
                            color: '#eee',
                            backgroundColor: '#334455',
                            padding: [2, 4],
                            borderRadius: 2
                        }
                    }
                }
            },
            data:[
                {value:335, name:'直达'},
                {value:310, name:'邮件营销'},
                {value:234, name:'联盟广告'},
                {value:135, name:'视频广告'},
                {value:1048, name:'百度'},
                {value:251, name:'谷歌'},
                {value:147, name:'必应'},
                {value:102, name:'其他'}
            ]
        }
    ]
};
        myChart.setOption(option);
</script>";

        return $script;

    }


    public function bzt_echart1($div_id,$data)
    {

        $script = "
       <script >
       var myChart = echarts.init(document.getElementById('".$div_id."'));
       option = {
        title : {
        text: '项目量统计',
        subtext: '单位（个）',
        x:'center'
    },
    tooltip : {
        trigger: 'item',
        formatter: \"{a} <br/>{b} : {c} ({d}%)\"
    },
    legend: {
        orient: 'vertical',
        left: 'left',
        data: [".$data['xAxis']."]
    },
     toolbox: {
        show : true,
        feature : {
            mark : {show: true},
            dataView : {show: true, readOnly: false},
            restore : {show: true},
            saveAsImage : {show: true}
        }
    },
    series : [
        {
            name: '项目量',
            type: 'pie',
            radius : '55%',
            center: ['50%', '60%'],
             itemStyle : {
                normal : {
                    label : {
                        position : 'inner',
                        formatter : function (params) {
                          return (params.percent - 0).toFixed(0) + '%'
                        }
                    },
                    labelLine : {
                        show : false
                    }
                },
                emphasis : {
                    label : {
                        show : true,
                        formatter : \"{b}{d}%\"
                    }
                }
            },
            data:[
            ";

        foreach($data['customer_volume'] as $key => $val){

            $script .="
                {value:".$val.", name:'".$data['pie_need'][$key]."'},
                ";
        }


        $script.="
            ],
            itemStyle: {
                emphasis: {
                    shadowBlur: 10,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                }
            }
        }
    ]
};

       myChart.setOption(option);
</script>
        ";


        return $script;
    }

    public function bzt_echart2($div_id,$data)
    {

        $script = "
       <script >
       var myChart = echarts.init(document.getElementById('".$div_id."'));
       option = {
        title : {
        text: '签约金额总量',
        subtext: '单位（元）',
        x:'center'
    },
    tooltip : {
        trigger: 'item',
        formatter: \"{a} <br/>{b} : {c} ({d}%)\"
    },
    legend: {
        orient: 'vertical',
        left: 'left',
        data: [".$data['xAxis']."]
    },
     toolbox: {
        show : true,
        feature : {
            mark : {show: true},
            dataView : {show: true, readOnly: false},
            restore : {show: true},
            saveAsImage : {show: true}
        }
    },
    series : [
        {
            name: '签约金额总量',
            type: 'pie',
            radius : '55%',
            center: ['50%', '60%'],
             itemStyle : {
                normal : {
                    label : {
                        position : 'inner',
                        formatter : function (params) {
                          return (params.percent - 0).toFixed(0) + '%'
                        }
                    },
                    labelLine : {
                        show : false
                    }
                },
                emphasis : {
                    label : {
                        show : true,
                        formatter : \"{b}{d}%\"
                    }
                }
            },
            data:[
            ";

        foreach($data['total_contract_amount'] as $key => $val){

            $script .="
                {value:".$val.", name:'".$data['pie_need'][$key]."'},
                ";
        }


        $script.="
            ],
            itemStyle: {
                emphasis: {
                    shadowBlur: 10,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                }
            }
        }
    ]
};

       myChart.setOption(option);
</script>
        ";


        return $script;
    }

    public function bzt_echart3($div_id,$data)
    {

        $script = "
       <script >
       var myChart = echarts.init(document.getElementById('".$div_id."'));
       option = {
        title : {
        text: '实际回款总量',
        subtext: '单位（元）',
        x:'center'
    },
    tooltip : {
        trigger: 'item',
        formatter: \"{a} <br/>{b} : {c} ({d}%)\"
    },
    legend: {
        orient: 'vertical',
        left: 'left',
        data: [".$data['xAxis']."]
    },
     toolbox: {
        show : true,
        feature : {
            mark : {show: true},
            dataView : {show: true, readOnly: false},
            restore : {show: true},
            saveAsImage : {show: true}
        }
    },
    series : [
        {
            name: '项目量',
            type: 'pie',
            radius : '55%',
            center: ['50%', '60%'],
             itemStyle : {
                normal : {
                    label : {
                        position : 'inner',
                        formatter : function (params) {
                          return (params.percent - 0).toFixed(0) + '%'
                        }
                    },
                    labelLine : {
                        show : false
                    }
                },
                emphasis : {
                    label : {
                        show : true,
                        formatter : \"{b}{d}%\"
                    }
                }
            },
            data:[
            ";

        foreach($data['total_amount'] as $key => $val){

            $script .="
                {value:".$val.", name:'".$data['pie_need'][$key]."'},
                ";
        }


        $script.="
            ],
            itemStyle: {
                emphasis: {
                    shadowBlur: 10,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                }
            }
        }
    ]
};

       myChart.setOption(option);
</script>
        ";


        return $script;
    }




    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function bzt_echart11($div_id,$data)
    {

        $script = "
       <script >
       var myChart = echarts.init(document.getElementById('".$div_id."'));
       option = {
        title : {
        text: '项目量统计',
        subtext: '单位（个）',
        x:'center'
    },
    tooltip : {
        trigger: 'item',
        formatter: \"{a} <br/>{b} : {c} ({d}%)\"
    },
    legend: {
        orient: 'vertical',
        left: 'left',
        data: [".$data['xAxis']."]
    },
     toolbox: {
        show : true,
        feature : {
            mark : {show: true},
            dataView : {show: true, readOnly: false},
            restore : {show: true},
            saveAsImage : {show: true}
        }
    },
    series : [
        {
            name: '项目量',
            type: 'pie',
            radius : '55%',
            center: ['50%', '60%'],
             itemStyle : {
                normal : {
                    label : {
                        position : 'inner',
                        formatter : function (params) {
                          return (params.percent - 0).toFixed(0) + '%'
                        }
                    },
                    labelLine : {
                        show : false
                    }
                },
                emphasis : {
                    label : {
                        show : true,
                        formatter : \"{b}{d}%\"
                    }
                }
            },
            data:[
            ";

        foreach($data['customer_volume'] as $key => $val){

            $script .="
                {value:".$val.", name:'".$data['pie_need'][$key]."'},
                ";
        }


        $script.="
            ],
            itemStyle: {
                emphasis: {
                    shadowBlur: 10,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                }
            }
        }
    ]
};

       myChart.setOption(option);
</script>
        ";


        return $script;
    }

    public function bzt_echart21($div_id,$data)
    {

        $script = "
       <script >
       var myChart = echarts.init(document.getElementById('".$div_id."'));
       option = {
        title : {
        text: '签约金额总量',
        subtext: '单位（元）',
        x:'center'
    },
    tooltip : {
        trigger: 'item',
        formatter: \"{a} <br/>{b} : {c} ({d}%)\"
    },
    legend: {
        orient: 'vertical',
        left: 'left',
        data: [".$data['xAxis']."]
    },
     toolbox: {
        show : true,
        feature : {
            mark : {show: true},
            dataView : {show: true, readOnly: false},
            restore : {show: true},
            saveAsImage : {show: true}
        }
    },
    series : [
        {
            name: '签约金额总量',
            type: 'pie',
            radius : '55%',
            center: ['50%', '60%'],
             itemStyle : {
                normal : {
                    label : {
                        position : 'inner',
                        formatter : function (params) {
                          return (params.percent - 0).toFixed(0) + '%'
                        }
                    },
                    labelLine : {
                        show : false
                    }
                },
                emphasis : {
                    label : {
                        show : true,
                        formatter : \"{b}{d}%\"
                    }
                }
            },
            data:[
            ";

        foreach($data['total_contract_amount'] as $key => $val){

            $script .="
                {value:".$val.", name:'".$data['pie_need'][$key]."'},
                ";
        }


        $script.="
            ],
            itemStyle: {
                emphasis: {
                    shadowBlur: 10,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                }
            }
        }
    ]
};

       myChart.setOption(option);
</script>
        ";


        return $script;
    }

    public function bzt_echart31($div_id,$data)
    {

        $script = "
       <script >
       var myChart = echarts.init(document.getElementById('".$div_id."'));
       option = {
        title : {
        text: '实际回款总量',
        subtext: '单位（元）',
        x:'center'
    },
    tooltip : {
        trigger: 'item',
        formatter: \"{a} <br/>{b} : {c} ({d}%)\"
    },
    legend: {
        orient: 'vertical',
        left: 'left',
        data: [".$data['xAxis']."]
    },
     toolbox: {
        show : true,
        feature : {
            mark : {show: true},
            dataView : {show: true, readOnly: false},
            restore : {show: true},
            saveAsImage : {show: true}
        }
    },
    series : [
        {
            name: '项目量',
            type: 'pie',
            radius : '55%',
            center: ['50%', '60%'],
             itemStyle : {
                normal : {
                    label : {
                        position : 'inner',
                        formatter : function (params) {
                          return (params.percent - 0).toFixed(0) + '%'
                        }
                    },
                    labelLine : {
                        show : false
                    }
                },
                emphasis : {
                    label : {
                        show : true,
                        formatter : \"{b}{d}%\"
                    }
                }
            },
            data:[
            ";

        foreach($data['total_amount'] as $key => $val){

            $script .="
                {value:".$val.", name:'".$data['pie_need'][$key]."'},
                ";
        }

        $script.="
            ],
            itemStyle: {
                emphasis: {
                    shadowBlur: 10,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                }
            }
        }
    ]
};

       myChart.setOption(option);
</script>
        ";
        return $script;
    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



    /**
     * $style（样式）  特殊版本,y轴多个比例尺
     *
     * @param string $div_id
     * @param $data
     * @param string $unit
     * @param string $theme
     * @return string
     */
    public function special_style($div_id = "pdo",$data,$theme = "智慧池图形展示",$unit = "单位: °C")
    {
        $script = "
        <script>
        var myChart = echarts.init(document.getElementById('".$div_id."'));
        option = {
        title : {
        text: '".$theme."',          //标题
        x: 'center',
        y:'top',
    },
    tooltip: {
        trigger: 'axis'
    },
    legend: {
        data:[".$data['legend']."],
        itemWidth: 20,             // 图例图形宽度
        itemHeight: 14,            // 图例图形高度
        x: 'center', // 'center' | 'left' | {number},
        y: '5%', // 'center' | 'bottom' | {number}
        backgroundColor: '#eee',
        borderColor: 'rgba(178,34,34,0.8)',
        //orient: 'vertical',                   //竖直的，默认是水平
    },
    xAxis: [
        {
            type: 'category',
            data: [".$data['xAxis']."]
        }
    ],
    yAxis: [
    ";
        $jj = 0;
        foreach($data['yAxis'] as $y)
        {
            if($jj == 0){
                $script.="
        {
            type: 'value',
            name: '".$y['unit']."',
            position: 'right',
            axisLabel: {
                formatter: '{value}'
            }
        },";
            }
            elseif($jj == 1)
            {
                $jj++;
                continue;
                $script.="
        {
            type: 'value',
            name: '".$y['unit']."',
            position: 'right',
            axisLabel: {
                formatter: '{value}'
            }
        },";
            }
            else
            {
                $script.="
        {
            type: 'value',
            name: '".$y['unit']."',
            position: 'left',
            axisLabel: {
                formatter: '{value}'
            }
        },";
            }

            $jj++;
        }
        $script.="

    ],
    series: [
    ";
        //  以下  1处应该为 $ii 但为了配合这个做的
        $ii = 0;
        foreach($data['series'] as $rows)
        {
            if($ii>1){$ii =1;}

            if($rows['type'] == "line")
            {
                if($ii>0)
                {
                    $script.=$this->line_unit($rows,$ii);
                }
                else
                {
                    $script.=$this->line_unit($rows);
                }
            }
            elseif($rows['type'] == "dotted")
            {
                if($ii>0)
                {
                    $script.=$this->dotted_unit($rows,$ii);
                }
                else
                {
                    $script.=$this->dotted_unit($rows);
                }
            }
            else
            {
                if($ii>0)
                {
                    $script.=$this->bar_unit($rows,$ii);
                }
                else
                {
                    $script.=$this->bar_unit($rows);
                }
            }
            $ii++;
        };

        $script.="]}
        myChart.setOption(option);
        </script>";

        return $script;
    }



    /**
     * $style（样式）  通用版
     *
     * @param string $div_id
     * @param $data
     * @param string $unit
     * @param string $theme
     * @return string            $scan_min y坐标最小值
     */
    public function wdj_style($div_id = "pdo",$data = '',$theme = "智慧池图形展示",$unit = "单位: °C",$scan_min = false)
    {
        $script = "
        <script>
        var myChart = echarts.init(document.getElementById('".$div_id."'));
        option = {
    title : {
        text: '".$data['text']."',
        subtext: '回款详情表',
//        sublink: 'http://e.weibo.com/1341556070/AizJXrAEa'
    },
    tooltip : {
        trigger: 'axis',
        axisPointer : {            // 坐标轴指示器，坐标轴触发有效
            type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
        },
        formatter: function (params){
            return params[0].name + '<br/>'
                   + params[0].seriesName + ' : ' + params[0].value + '<br/>'
                   + params[1].seriesName + ' : ' + (params[1].value + params[0].value);
        }
    },
    legend: {
        selectedMode:false,
        data:['".$data['name'][1]."', '".$data['name'][0]."']
    },
    toolbox: {
        show : true,
        feature : {
            mark : {show: true},
            dataView : {show: true, readOnly: false},
            restore : {show: true},
            saveAsImage : {show: true}
        }
    },
    calculable : true,
    xAxis : [
        {
            type : 'category',
            data : [".$data['xAxis']."]
        }
    ],
    yAxis : [
        {
            type : 'value',
            boundaryGap: [0, 0.1]
        }
    ],
    series : [
        {
            name:'".$data['name'][1]."',
            type:'bar',
            stack: 'sum',
            barCategoryGap: '50%',
            itemStyle: {
                normal: {
                    color: 'tomato',
                    barBorderColor: 'tomato',
                    barBorderWidth: 6,
                    barBorderRadius:0,
                    label : {
                        show: true, position: 'insideTop'
                    }
                }
            },
            data:[".$data['data'][1]."]
        },
        {
            name:'".$data['name'][0]."',
            type:'bar',
            stack: 'sum',
            itemStyle: {
                normal: {
                    color: '#fff',
                    barBorderColor: 'tomato',
                    barBorderWidth: 6,
                    barBorderRadius:0,
                    label : {
                        show: true,
                        position: 'top',
                        formatter: function (params) {
                            for (var i = 0, l = option.xAxis[0].data.length; i < l; i++) {
                                if (option.xAxis[0].data[i] == params.name) {
                                    return option.series[0].data[i] + params.value;
                                }
                            }
                        },
                        textStyle: {
                            color: 'tomato'
                        }
                    }
                }
            },
            data:[".$data['data'][0]."]
        }
    ]
};

        myChart.setOption(option);
        </script>";
        return $script;
    }


    /**
     * 最近四个月
     *
     * @param string $div_id
     * @param string $data
     * @return string
     */
    public function hkl_ylb($div_id = "pdo",$data = '')
    {
        $script = "
        <script>
        var myChart = echarts.init(document.getElementById('".$div_id."'));
        option = {
             title : {
                text: '".$data['text']."',          //标题
                x: 'center',
                y:'top',
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'cross',
                    crossStyle: {
                        color: '#999'
                    }
                }
            },
             grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
            toolbox: {
                feature: {
                    dataView: {show: true, readOnly: false},
                    magicType: {show: true, type: ['line', 'bar']},
                    restore: {show: true},
                    saveAsImage: {show: true}
                }
            },
            legend: {
                data:['".implode("','",$data['name'])."'],
                 x: 'center', // 'center' | 'left' | {number},
                 y: '5%', // 'center' | 'bottom' | {number}
            },
            xAxis: [
                {
                    type: 'category',
                    data: [".$data['xAxis']."],
                    axisPointer: {
                        type: 'shadow'
                    },
                    splitLine:{show:true}
                }
            ],
            yAxis: [
                {
                    type: 'value',
                    name: '金额(元)',
                    axisLabel: {
                        formatter: '{value} 元'
                    },
                      x: 'left', // 'center' | 'left' | {number},
                      y: 'bottom', // 'center' | 'bottom' | {number}
                      splitLine:{show:false}
                },
                {
                    type: 'value',
                    name: '回款率(%)',
                    axisLabel: {
                        formatter: '{value}%'
                    },
                    splitLine:{show:false}
                }
            ],
            series: [
                {
                    name:'".$data['name'][0]."',
                    type:'bar',
                    barWidth: 25,
                    data:[".$data['data'][0]."],
                    itemStyle:{normal:{color:'#FBCE0F'}},
                    label: {
                        normal: {
                            show: false,
                            position: 'top',
                            right:'10',
                            formatter: '签约总额{c}元',
                        }
                    },
                    textStyle:{
                        fontSize:'20px',
                        color:'blue',
                        align:'center'
                        },
                    showAllSymbol: true,                //折线出圈圈，全部显示
                    symbolSize:10,                      //圈圈大小
                },
                {
                    name:'".$data['name'][1]."',
                    type:'bar',
                    barWidth: 25,
                    data:[".$data['data'][1]."],
                    itemStyle:{normal:{color:'#DA70D6'}},
                    label: {
                        normal: {
                            show: false,
                            position: 'top',
                            formatter: '回款{c}元',
                        }
                    }
                },
                {
                    name:'".$data['name'][2]."',
                    type:'bar',
                    barWidth: 25,
                    data:[".$data['data'][2]."],
                    itemStyle:{normal:{color:'#6495ED'}},
                    label: {
                        normal: {
                            show: false,
                            position: 'top',
                            formatter: '欠款{c}元',
                        }
                    }
                },
                {
                    name:'".$data['name'][3]."',
                    type:'line',
                    yAxisIndex: 1,
                    data:[".$data['data'][3]."],
                    lineStyle:{normal:{type:'dotted'}},
                    label: {
                        normal: {
                            show: true,
                            position: 'top',
                            right:'10',
                            formatter: '回款率：{c}%',
                        }
                    },
                    smooth:true,    //折线变平滑
                    itemStyle:{normal:{color:'#D7504B'}},
                    textStyle:{
                        fontSize:'20px',
                        color:'blue',
                        align:'center'
                        },
                    showAllSymbol: true,                //折线出圈圈，全部显示
                    symbolSize:10,                      //圈圈大小
                }
            ]
        };
        myChart.setOption(option);
        </script>";
        return $script;
    }

    public function hkl_ylb_all($div_id = "pdo",$data = '')
    {
        $script = "
        <script>
        var myChart = echarts.init(document.getElementById('".$div_id."'));
        option = {
             title : {
                text: '".$data['text']."',          //标题
                x: 'center',
                y:'top',
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'cross',
                    crossStyle: {
                        color: '#999'
                    }
                }
            },
             grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
            toolbox: {
                feature: {
                    dataView: {show: true, readOnly: false},
                    magicType: {show: true, type: ['line', 'bar']},
                    restore: {show: true},
                    saveAsImage: {show: true}
                }
            },
            legend: {
                data:['".implode("','",$data['name'])."'],
                 x: 'center', // 'center' | 'left' | {number},
                 y: '5%', // 'center' | 'bottom' | {number}
            },
            xAxis: [
                {
                    type: 'category',
                    data: [".$data['xAxis']."],
                    axisPointer: {
                        type: 'shadow'
                    },
                    splitLine:{show:true}
                }
            ],
            yAxis: [
                {
                    type: 'value',
                    name: '金额(元)',
                    axisLabel: {
                        formatter: '{value} 元'
                    },
                      x: 'left', // 'center' | 'left' | {number},
                      y: 'bottom', // 'center' | 'bottom' | {number}
                      splitLine:{show:false}
                },
                {
                    type: 'value',
                    name: '回款率(%)',
                    axisLabel: {
                        formatter: '{value}%'
                    },
                    splitLine:{show:false}
                }
            ],
            series: [
                {
                    name:'".$data['name'][0]."',
                    type:'bar',
                    barWidth: 20,
                    data:[".$data['data'][0]."],
                    itemStyle:{normal:{color:'#FBCE0F'}},
                    label: {
                        normal: {
                            show: true,
                            position: 'top',
                            formatter: '{c}元',
                        }
                    }
                },
                {
                    name:'".$data['name'][1]."',
                    type:'bar',
                    barWidth: 20,
                    data:[".$data['data'][1]."],
                    itemStyle:{normal:{color:'#6495ED'}},
                    label: {
                        normal: {
                            show: true,
                            position: 'top',
                            formatter: '{c}元',
                        }
                    }
                },
                {
                    name:'".$data['name'][2]."',
                    type:'bar',
                    barWidth: 20,
                    data:[".$data['data'][2]."],
                    itemStyle:{normal:{color:'#DA70D6'}},
                    label: {
                        normal: {
                            show: true,
                            position: 'top',
                            formatter: '{c}元',
                        }
                    }
                },
                {
                    name:'".$data['name'][3]."',
                    type:'line',
                    yAxisIndex: 1,
                    data:[".$data['data'][3]."],
                    label: {
                        normal: {
                            show: true,
                            position: 'top',
                            right:'10',
                            formatter: '{c}%',
                        }
                    },
                    smooth:true,    //折线变平滑
                    itemStyle:{normal:{color:'#D7504B'}},
                    textStyle:{
                        fontSize:'20px',
                        color:'blue',
                        align:'center'
                        },
                    showAllSymbol: true,                //折线出圈圈，全部显示
                    symbolSize:10,                      //圈圈大小
                }
            ]
        };
        myChart.setOption(option);
        </script>";
        return $script;
    }

    public function hkl_ylb_all_1($div_id = "pdo",$data = '')
    {
        $script = "
        <script>
        var myChart = echarts.init(document.getElementById('".$div_id."'));
        option = {
             title : {
                text: '".$data['text']."',          //标题
                x: 'center',
                y:'top',
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'cross',
                    crossStyle: {
                        color: '#999'
                    }
                }
            },
             grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
            toolbox: {
                feature: {
                    dataView: {show: true, readOnly: false},
                    magicType: {show: true, type: ['line', 'bar']},
                    restore: {show: true},
                    saveAsImage: {show: true}
                }
            },
            legend: {
                data:['".implode("','",$data['name'])."'],
                 x: 'center', // 'center' | 'left' | {number},
                 y: '5%', // 'center' | 'bottom' | {number}
            },
            xAxis: [
                {
                    type: 'category',
                    data: [".$data['xAxis']."],
                    axisPointer: {
                        type: 'shadow'
                    },
                    splitLine:{show:true}
                }
            ],
            yAxis: [
                {
                    type: 'value',
                    name: '金额(元)',
                    axisLabel: {
                        formatter: '{value} 元'
                    },
                      x: 'top', // 'center' | 'left' | {number},
                      y: 'bottom', // 'center' | 'bottom' | {number}
                      splitLine:{show:false}
                },
                {
                    type: 'value',
                    name: '回款率(%)',
                    axisLabel: {
                        formatter: '{value}%'
                    },
                    splitLine:{show:false}
                }
            ],
            series: [
                {
                    name:'".$data['name'][0]."',
                    type:'bar',
                    barWidth: 25,
                    data:[".$data['data'][0]."],
                    itemStyle:{normal:{color:'#FBCE0F'}},
                    label: {
                        normal: {
                            show: false,
                            position: 'top',
                            formatter: '签约金额{c}元',
                        }
                    }
                },
                {
                    name:'".$data['name'][1]."',
                    type:'bar',
                    barWidth: 25,
                    data:[".$data['data'][1]."],
                    itemStyle:{normal:{color:'#6495ED'}},
                    label: {
                        normal: {
                            show: false,
                            position: 'top',
                            formatter: '回款{c}元',
                        }
                    }
                },
                {
                    name:'".$data['name'][2]."',
                    type:'bar',
                    barWidth: 25,
                    data:[".$data['data'][2]."],
                    itemStyle:{normal:{color:'#DA70D6'}},
                    label: {
                        normal: {
                            show: false,
                            position: 'top',
                            formatter: '欠款{c}元',
                        }
                    }
                },
                {
                    name:'".$data['name'][3]."',
                    type:'line',
                    yAxisIndex: 1,
                    data:[".$data['data'][3]."],
                    label: {
                        normal: {
                            show: true,
                            position: 'top',
                            right:'10',
                            formatter: '{c}%',
                        }
                    },
                    smooth:true,    //折线变平滑
                    itemStyle:{normal:{color:'#D7504B'}},
                    textStyle:{
                        fontSize:'20px',
                        color:'blue',
                        align:'center'
                        },
                    showAllSymbol: true,                //折线出圈圈，全部显示
                    symbolSize:10,                      //圈圈大小
                }
            ]
        };
        myChart.setOption(option);
        </script>";
        return $script;
    }




    ///////////////////////////////////////////////////////////////////////////////////////////////////////客户开拓
    /**
     * 最近四个月
     *
     * @param string $div_id
     * @param string $data
     * @return string
     */
    public function _cust_four_ylb($div_id = "pdo",$data = '')
    {
        $script = "
        <script>
        var myChart = echarts.init(document.getElementById('".$div_id."'));
        option = {
             title : {
                text: '".$data['text']."',          //标题
                x: 'center',
                y:'top',
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'cross',
                    crossStyle: {
                        color: '#999'
                    }
                }
            },
             grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
            toolbox: {
                feature: {
                    dataView: {show: true, readOnly: false},
                    magicType: {show: true, type: ['line', 'bar']},
                    restore: {show: true},
                    saveAsImage: {show: true}
                }
            },
            legend: {
                data:['".implode("','",$data['name'])."'],
                 x: 'center', // 'center' | 'left' | {number},
                 y: '5%', // 'center' | 'bottom' | {number}
            },
            xAxis: [
                {
                    type: 'category',
                    data: [".$data['xAxis']."],
                    axisPointer: {
                        type: 'shadow'
                    },
                    splitLine:{show:true}
                }
            ],
            yAxis: [
                {
                    type: 'value',
                    name: '单位(个)',
                    axisLabel: {
                        formatter: '{value}'
                    },
                      x: 'left', // 'center' | 'left' | {number},
                      y: 'bottom', // 'center' | 'bottom' | {number}
                      splitLine:{show:false}
                }
            ],
            series: [
                {
                    name:'".$data['name'][0]."',
                    type:'bar',
                    barWidth: 25,
                    data:[".$data['data'][0]."],
                    itemStyle:{normal:{color:'#D7504B'}},
                    label: {
                        normal: {
                            show: true,
                            position: 'top',
                            right:'10',
                            formatter: '{c}',
                        }
                    },
                    textStyle:{
                        fontSize:'20px',
                        color:'blue',
                        align:'center'
                        },
                    showAllSymbol: true,                //折线出圈圈，全部显示
                    symbolSize:10,                      //圈圈大小
                },
                {
                    name:'".$data['name'][1]."',
                    type:'bar',
                    barWidth: 25,
                    data:[".$data['data'][1]."],
                    itemStyle:{normal:{color:'#B5C334'}},
                    label: {
                        normal: {
                            show: true,
                            position: 'top',
                            formatter: '{c}',
                        }
                    }
                },
                {
                    name:'".$data['name'][2]."',
                    type:'bar',
                    barWidth: 25,
                    data:[".$data['data'][2]."],
                    itemStyle:{normal:{color:'#27727B'}},
                    label: {
                        normal: {
                            show: true,
                            position: 'top',
                            formatter: '{c}',
                        }
                    }
                }
            ]
        };
        myChart.setOption(option);
        </script>";
        return $script;
    }
}