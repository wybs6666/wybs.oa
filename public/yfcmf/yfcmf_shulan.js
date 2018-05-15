/* 客戶来源 */
$(function () {
    $('body').on('click','.sourceedit-btn',function () {
        var $url = this.href,
            val = $(this).data('id');
        $.post($url, {source_id: val}, function (data) {
            if (data.code == 1) {
                $("#myModaledit").show(300);
                $("#editsource_id").val(data.source_id);
                $("#editsource_name").val(data.source_name);
                $("#editsource_order").val(data.source_order);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});
//客戶来源
function customer_souadd(val) {
    $('#customer_source').val(val);
}


/****************************************************************************/





/* company增加编辑表单，带检查 */
$(function () {
    $('.companyform').ajaxForm({
        beforeSubmit: checkcompanyForm, // 此方法主要是提交前执行的方法，根据需要设置
        success: complete, // 这是提交后的方法
        dataType: 'json'
    });
});

//company表单检查
function checkcompanyForm() {
    //var company_name = $.trim($('input[name="company_name"]').val()); //获取INPUT值
    //var myReg = /^[\u4e00-\u9fa5]+$/;//验证中文
    //if (company_username.indexOf(" ") >= 0) {
    //    layer.alert('登录用户名包含了空格，请重新输入', {icon: 5}, function (index) {
    //        layer.close(index);
    //        $('#company_username').focus();
    //    });
    //    return false;
    //}
    //if (myReg.test(company_name)) {
    //    layer.alert('用户名必须是字母，数字，符号', {icon: 5}, function (index) {
    //        layer.close(index);
    //        $('#company_username').focus();
    //    });
    //    return false;
    //}

    /**
     * 验证电话格式
     */
    //if (!$("#company_mobile").val().match(/^(((13[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/))
    //{
    //    layer.alert('电话号码格式不正确', {icon: 5}, function (index)
    //    {
    //        layer.close(index);
    //        $('#company_mobile').focus();
    //    });
    //    return false;
    //}
}

/****************************************************************************/


/* company增加编辑表单，带检查 */
$(function () {
    $('.custform').ajaxForm({
        beforeSubmit: checkcompanyForm, // 此方法主要是提交前执行的方法，根据需要设置
        success: complete, // 这是提交后的方法
        dataType: 'json'
    });
});
/****************************************************************************/


/* 回款周期编辑 */
$(function () {
    $('body').on('click','.collection_periodedit-btn',function ()
    {
        var $url = this.href,
            val = $(this).data('id');
        $.post($url, {collection_period_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit").show(300);
                $("#editcollection_period_id").val(data.collection_period_id);
                $("#editcollection_period_name").val(data.collection_period_name);
                $("#editcollection_period_order").val(data.collection_period_order);
                $("#editequivalent_months").val(data.equivalent_months);
            }
            else
            {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});
//来源
function collection_period_add(val) {
    $('#news_collection_period').val(val);
}

/* 项目状态 */
$(function () {
    $('body').on('click','.customer_statusedit-btn',function () {
        var $url = this.href,
            val = $(this).data('id');
        $.post($url, {customer_status_id: val}, function (data)
        {
            if (data.code == 1) {
                $("#myModaledit").show(300);
                $("#editcustomer_status_id").val(data.customer_status_id);
                $("#editcustomer_status_name").val(data.customer_status_name);
                $("#editcustomer_status_order").val(data.customer_status_order);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});
//项目状态详情表
function customer_status_add(val) {
    $('#news_customer_status').val(val);
}



/* 导出勾选 */
$(function () {
    $('body').on('click','.customer_statusedit-btn1',function () {
        var $url = this.href,
            val = $(this).data('id');
        $.post($url, {customer_status_id: val}, function (data)
        {
            if (data.code == 1) {
                $("#myModaledit11").show(300);
                $("#editcustomer_status_id").val(data.customer_status_id);
                $("#editcustomer_status_name").val(data.customer_status_name);
                $("#editcustomer_status_order").val(data.customer_status_order);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});



/* 机构信用等级 */
$(function () {
    $('body').on('click','.credit_ratingedit-btn',function ()
    {
        var $url = this.href,
            val = $(this).data('id');

        $.post($url, {credit_rating_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit").show(300);
                $("#editcredit_rating_id").val(data.credit_rating_id);
                $("#editcredit_rating_name").val(data.credit_rating_name);
                $("#editcredit_rating_explain").val(data.credit_rating_explain);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});
//机构信用等级
function credit_rating_add(val) {
    $('#news_credit_rating').val(val);
}


/* 服务项目 */
$(function () {
    $('body').on('click','.service_itemsedit-btn',function () {
        var $url = this.href,
            val = $(this).data('id');
        $.post($url, {service_items_id: val}, function (data) {
            if (data.code == 1) {
                $("#myModaledit").show(300);
                $("#editservice_items_id").val(data.service_items_id);
                $("#editservice_items_name").val(data.service_items_name);
                $("#editservice_items_alia").val(data.service_items_alia);
                //$("#editservice_items_explain").val(data.service_items_explain);
                $("#editservice_items_order").val(data.service_items_order);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});
//服务项目
function service_itemsadd(val) {
    $('#customer_service_items').val(val);
}

/* 对接人详情 */
$(function ()
{
    $('body').on('click','.customer_waiteredit-btn',function ()
    {
        var $url = this.href,
            val = $(this).data('id');
        $.post($url, {customer_waiter_id: val}, function (data) {
            if (data.code == 1) {
                $("#myModaledit").show(300);
                $("#editcustomer_waiter_id").val(data.customer_waiter_id);
                $("#editcustomer_waiter_name").val(data.customer_waiter_name);
                $("#editcustomer_waiter_order").val(data.customer_waiter_order);
                $("#editwaiter_type").val(data.waiter_type);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});
//对接人详情
function customer_waiter_add(val)
{
    $('#news_customer_waiter').val(val);
}

/* 合同周期详情 */
$(function () {
    $('body').on('click','.customer_contract_cycleedit-btn',function () {
        var $url = this.href,
            val = $(this).data('id');
        $.post($url, {customer_contract_cycle_id: val}, function (data)
        {
            if (data.code == 1) {
                //alert(data.equivalent_months);
                //alert(data.customer_contract_cycle_id);
                $("#myModaledit").show(300);
                $("#editcustomer_contract_cycle_id").val(data.customer_contract_cycle_id);
                $("#editcustomer_contract_cycle_name").val(data.customer_contract_cycle_name);
                $("#editequivalent_months").val(data.equivalent_months);
                $("#editcustomer_contract_cycle_order").val(data.customer_contract_cycle_order);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});
//合同周期详情
function customer_contract_cycle_add(val) {
    $('#news_customer_contract_cycle').val(val);
}








/*  根据 执行时间和 签约时间  自动生成 到期时间 */
$(function () {
    $('body').on('click','.customer_expiration_date_edit',function ()
    {
        //合同周期
        var customer_contract_cycle_id = $("#customer_contract_cycle_id option:selected").val();


        //执行时间
        var customer_execution_time = $("#customer_execution_time").val();

        var $url = '/admin/customer/cmposite_expiration_date';

        $.post($url, {customer_contract_cycle_id: customer_contract_cycle_id,customer_execution_time:customer_execution_time}, function (data)
        {
            $('#customer_expiration_date_come').show();
            $('#customer_expiration_date').val(data.need_data);
        }, "json");
        return false;
    });
});



/* 回款类型 */
$(function () {
    $('body').on('click','.customer_payment_typeedit-btn',function ()
    {
        var $url = this.href,
            val = $(this).data('id');

        $.post($url, {customer_payment_type_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit").show(300);
                $("#editcustomer_payment_type_id").val(data.customer_payment_type_id);
                $("#editcustomer_payment_type_name").val(data.customer_payment_type_name);
                $("#editcustomer_payment_type_order").val(data.customer_payment_type_order);
                $("#editnumber_of_periods").val(data.number_of_periods);
                $("#editamount_proportion").val(data.amount_proportion);
                $("#edittime_scale").val(data.time_scale);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});


/* 回款列表 */
$(function () {
    $('body').on('click','.back_paymentedit-btn',function ()
    {
        var $url = this.href,
            val = $(this).data('id');
        $.post($url, {back_payment_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit").show(300);
                $("#editback_payment_id").val(data.back_payment_id);
                $("#editcustomer_id").val(data.customer_id);
                $("#editnumber_of_periods").val(data.number_of_periods);
                $("#editamount_of_money").val(data.amount_of_money);
                $("#editback_payment_time").val(data.back_payment_time);
                $("#editactual_amount_of_money").val(data.actual_amount_of_money);
                $("#editactual_back_payment_time").val(data.actual_back_payment_time);
                $("#edittotal_amount").val(data.total_amount);
                $("#editactual_total_amount").val(data.actual_total_amount);
                $("#edittotal_number_of_periods").val(data.total_number_of_periods);
                $("#editremark").val(data.remark);

            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});

//模态框状态
$(document).ready(function () {
    $("#myModaledit1").hide();
    $("#gb1").click(function () {
        $("#myModaledit1").hide(200);
    });
    $("#gbb1").click(function () {
        $("#myModaledit1").hide(200);
    });
    $("#gbbb1").click(function () {
        $("#myModaledit1").hide(200);
    });

    $("#myModaledit2").hide();
    $("#gb2").click(function () {
        $("#myModaledit2").hide(200);
    });
    $("#gbb2").click(function () {
        $("#myModaledit2").hide(200);
    });
    $("#gbbb2").click(function () {
        $("#myModaledit2").hide(200);
    });

    $("#gbb1_back_payment_detail21").click(function () {
        $("#myModaledit21").hide(200);
    });
});
$(document).ready(function () {
    $("#myModal1").hide();
    $("#gb1").click(function () {
        $("#myModal1").hide(200);
    });
    $("#gbb1").click(function () {
        $("#myModal1").hide(200);
    });
    $("#gbbb1").click(function () {
        $("#myModal1").hide(200);
    });
});



/* 回款列表详情 */
$(function () {
    $('body').on('click','.back_paymentedit-btn1',function ()
    {
        var $url = this.href,
            val = $(this).data('id');

        $.post($url, {customer_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit1").show(300);
                $("#back_payment_detail1").html(data.content);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});


/* 回款列表详情 */
$(function () {
    $('body').on('click','.back_paymentedit-btn2',function ()
    {
        var $url = this.href,
            val = $(this).data('id');

        $.post($url, {company_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit1").show(300);
                $("#back_payment_detail1").html(data.content);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});


/* 回款列表详情 */
$(function () {
    $('body').on('click','.back_paymentedit-btn3',function ()
    {
        var $url = this.href,
            val = $(this).data('id');

        $.post($url, {back_payment_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit1").show(300);
                $("#back_payment_detail1").html(data.content);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});

/* 公司详情详情 */
$(function () {
    $('body').on('click','.back_paymentedit-btn4',function ()
    {
        var $url = this.href,
            val = $(this).data('id');

        $.post($url, {company_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit2").show(300);
                $("#back_payment_detail2").html(data.content);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});




$(function () {
    /*商务对接人*/
    $('body').on('change','.ajax_change_customer_waiter_1',function ()
    {
        var obj=$(this).siblings('.action');
        var old_id=obj.find('.cancel-change-waiter_1').data('waiter_1'),new_id=$(this).val();
        if(old_id != new_id)
        {
            obj.find('.change-waiter_1').data('waiter_1',new_id);
            obj.removeClass('none');
        }
        else
        {
            obj.addClass('none');
        }
    }).on('click','a.change-waiter_1',function ()
    {
        var $url=this.href,$customer_waiter_1=$(this).data('waiter_1'),$n_id=$(this).data('id');
        var obj=$(this);
        $.post($url,{customer_waiter_1:$customer_waiter_1,n_id:$n_id}, function (data) {
            if (data.code==1) {
                obj.parent().addClass('none');
                obj.siblings('.cancel-change-waiter_1').data('waiter_1',$customer_waiter_1);
                layer.msg(data.msg,{icon: 6});
            }else{
                layer.msg(data.msg,{icon: 5});
            }
        }, "json");
        return false;
    }).on('click','a.cancel-change-waiter_1',function () {
        var old_id=$(this).data('waiter_1'),obj=$(this).parent();
        obj.addClass('none').siblings('.ajax_change_customer_waiter_1').val(old_id);
        return false;
    });
});

$(function () {
    /*技术对接人*/
    $('body').on('change','.ajax_change_customer_waiter_2',function ()
    {
        var obj=$(this).siblings('.action');
        var old_id=obj.find('.cancel-change-waiter_2').data('waiter_2'),new_id=$(this).val();
        if(old_id != new_id)
        {
            obj.find('.change-waiter_2').data('waiter_2',new_id);
            obj.removeClass('none');
        }
        else
        {
            obj.addClass('none');
        }
    }).on('click','a.change-waiter_2',function ()
    {
        var $url=this.href,$customer_waiter_2=$(this).data('waiter_2'),$n_id=$(this).data('id');
        var obj=$(this);
        $.post($url,{customer_waiter_2:$customer_waiter_2,n_id:$n_id}, function (data) {
            if (data.code==1) {
                obj.parent().addClass('none');
                obj.siblings('.cancel-change-waiter_2').data('waiter_2',$customer_waiter_2);
                layer.msg(data.msg,{icon: 6});
            }else{
                layer.msg(data.msg,{icon: 5});
            }
        }, "json");
        return false;
    }).on('click','a.cancel-change-waiter_2',function () {
        var old_id=$(this).data('waiter_2'),obj=$(this).parent();
        obj.addClass('none').siblings('.ajax_change_customer_waiter_2').val(old_id);
        return false;
    });
});



$(function () {
    /*信用等级*/
    $('body').on('change','.ajax_change_customer_credit_rating',function ()
    {
        var obj=$(this).siblings('.action');
        var old_id=obj.find('.cancel-change-rating').data('rating'),new_id=$(this).val();
        if(old_id != new_id)
        {
            obj.find('.change-rating').data('rating',new_id);
            obj.removeClass('none');
        }
        else
        {
            obj.addClass('none');
        }
    }).on('click','a.change-rating',function ()
    {
        var $url=this.href,$customer_credit_rating=$(this).data('rating'),$n_id=$(this).data('id');
        var obj=$(this);

        $.post($url,{customer_credit_rating:$customer_credit_rating,n_id:$n_id}, function (data)
        {
            if (data.code==1)
            {
                obj.parent().addClass('none');
                obj.siblings('.cancel-change-rating').data('rating',$customer_credit_rating);
                layer.msg(data.msg,{icon: 6});
            }
            else
            {
                layer.msg(data.msg,{icon: 5});
            }
        }, "json");
        return false;
    }).on('click','a.cancel-change-rating',function () {
        var old_id=$(this).data('rating'),obj=$(this).parent();
        obj.addClass('none').siblings('.ajax_change_customer_credit_rating').val(old_id);
        return false;
    });
});


$(function () {
    /*项目状态*/
    $('body').on('change','.ajax_change_customer_status_doing',function ()
    {
        var obj=$(this).siblings('.action');
        var old_id=obj.find('.cancel-change-doing').data('doing'),new_id=$(this).val();
        if(old_id != new_id)
        {
            obj.find('.change-doing').data('doing',new_id);
            obj.removeClass('none');
        }
        else
        {
            obj.addClass('none');
        }
    }).on('click','a.change-doing',function ()
    {
        var $url=this.href,$customer_status_doing=$(this).data('doing'),$n_id=$(this).data('id');
        var obj=$(this);
        $.post($url,{customer_status_doing:$customer_status_doing,n_id:$n_id}, function (data) {
            if (data.code==1) {
                obj.parent().addClass('none');
                obj.siblings('.cancel-change-doing').data('doing',$customer_status_doing);
                layer.msg(data.msg,{icon: 6});
            }else{
                layer.msg(data.msg,{icon: 5});
            }
        }, "json");
        return false;
    }).on('click','a.cancel-change-doing',function () {
        var old_id=$(this).data('doing'),obj=$(this).parent();
        obj.addClass('none').siblings('.ajax_change_customer_status_doing').val(old_id);
        return false;
    });
});




$(function () {
    /*回款周期*/
    $('body').on('change','.ajax_change_customer_collection_period',function ()
    {
        var obj=$(this).siblings('.action');
        var old_id=obj.find('.cancel-change-period').data('period'),new_id=$(this).val();
        if(old_id != new_id)
        {
            obj.find('.change-period').data('period',new_id);
            obj.removeClass('none');
        }
        else
        {
            obj.addClass('none');
        }
    }).on('click','a.change-period',function ()
    {
        var $url=this.href,$customer_collection_period=$(this).data('period'),$n_id=$(this).data('id');
        var obj=$(this);
        $.post($url,{customer_collection_period:$customer_collection_period,n_id:$n_id}, function (data) {
            if (data.code==1) {
                obj.parent().addClass('none');
                obj.siblings('.cancel-change-period').data('period',$customer_collection_period);
                layer.msg(data.msg,{icon: 6});
            }else{
                layer.msg(data.msg,{icon: 5});
            }
        }, "json");
        return false;
    }).on('click','a.cancel-change-period',function () {
        var old_id=$(this).data('period'),obj=$(this).parent();
        obj.addClass('none').siblings('.ajax_change_customer_collection_period').val(old_id);
        return false;
    });
});



$(function () {
    /*合同周期*/
    $('body').on('change','.ajax_change_customer_contract_cycle',function ()
    {
        var obj=$(this).siblings('.action');
        var old_id=obj.find('.cancel-change-cycle').data('cycle'),new_id=$(this).val();
        if(old_id != new_id)
        {
            obj.find('.change-cycle').data('cycle',new_id);
            obj.removeClass('none');
        }
        else
        {
            obj.addClass('none');
        }
    }).on('click','a.change-cycle',function ()
    {
        var $url=this.href,$customer_contract_cycle=$(this).data('cycle'),$n_id=$(this).data('id');
        var obj=$(this);
        $.post($url,{customer_contract_cycle:$customer_contract_cycle,n_id:$n_id}, function (data) {
            if (data.code==1) {
                obj.parent().addClass('none');
                obj.siblings('.cancel-change-cycle').data('cycle',$customer_contract_cycle);
                layer.msg(data.msg,{icon: 6});
            }else{
                layer.msg(data.msg,{icon: 5});
            }
        }, "json");
        return false;
    }).on('click','a.cancel-change-cycle',function () {
        var old_id=$(this).data('cycle'),obj=$(this).parent();
        obj.addClass('none').siblings('.ajax_change_customer_contract_cycle').val(old_id);
        return false;
    });
});


$(function () {
    /*回款类型*/
    $('body').on('change','.ajax_change_customer_payment_type',function ()
    {
        var obj=$(this).siblings('.action');
        var old_id=obj.find('.cancel-change-type').data('type'),new_id=$(this).val();
        if(old_id != new_id)
        {
            obj.find('.change-type').data('type',new_id);
            obj.removeClass('none');
        }
        else
        {
            obj.addClass('none');
        }
    }).on('click','a.change-type',function ()
    {
        var $url=this.href,$customer_payment_type=$(this).data('type'),$n_id=$(this).data('id');
        var obj=$(this);
        $.post($url,{customer_payment_type:$customer_payment_type,n_id:$n_id}, function (data) {
            if (data.code==1) {
                obj.parent().addClass('none');
                obj.siblings('.cancel-change-type').data('type',$customer_payment_type);
                layer.msg(data.msg,{icon: 6});
            }else{
                layer.msg(data.msg,{icon: 5});
            }
        }, "json");
        return false;
    }).on('click','a.cancel-change-type',function () {
        var old_id=$(this).data('type'),obj=$(this).parent();
        obj.addClass('none').siblings('.ajax_change_customer_payment_type').val(old_id);
        return false;
    });
});


$(function () {
    /*服务项目*/
    $('body').on('change','.ajax_change_customer_service_items',function ()
    {
        var obj=$(this).siblings('.action');
        var old_id=obj.find('.cancel-change-items').data('items'),new_id=$(this).val();
        if(old_id != new_id)
        {
            obj.find('.change-items').data('items',new_id);
            obj.removeClass('none');
        }
        else
        {
            obj.addClass('none');
        }
    }).on('click','a.change-items',function ()
    {
        var $url=this.href,$customer_service_items=$(this).data('items'),$n_id=$(this).data('id');
        var obj=$(this);
        $.post($url,{customer_service_items:$customer_service_items,n_id:$n_id}, function (data) {
            if (data.code==1) {
                obj.parent().addClass('none');
                obj.siblings('.cancel-change-items').data('items',$customer_service_items);
                layer.msg(data.msg,{icon: 6});
            }else{
                layer.msg(data.msg,{icon: 5});
            }
        }, "json");
        return false;
    }).on('click','a.cancel-change-items',function () {
        var old_id=$(this).data('items'),obj=$(this).parent();
        obj.addClass('none').siblings('.ajax_change_customer_service_items').val(old_id);
        return false;
    });
});


$(function () {
    /*公司名*/
    $('body').on('change','.ajax_change_customer_company1_name1',function ()
    {
        var obj=$(this).siblings('.action');
        var old_id=obj.find('.cancel-change-name1').data('name1'),new_id=$(this).val();
        if(old_id != new_id)
        {
            obj.find('.change-name1').data('name1',new_id);
            obj.removeClass('none');
        }
        else
        {
            obj.addClass('none');
        }
    }).on('click','a.change-name1',function ()
    {
        var $url=this.href,$customer_company1_name1=$(this).data('name1'),$n_id=$(this).data('id');
        var obj=$(this);
        $.post($url,{customer_company1_name1:$customer_company1_name1,n_id:$n_id}, function (data) {
            if (data.code==1) {
                obj.parent().addClass('none');
                obj.siblings('.cancel-change-name1').data('name1',$customer_company1_name1);
                layer.msg(data.msg,{icon: 6});
            }else{
                layer.msg(data.msg,{icon: 5});
            }
        }, "json");
        return false;
    }).on('click','a.cancel-change-name1',function () {
        var old_id=$(this).data('name1'),obj=$(this).parent();
        obj.addClass('none').siblings('.ajax_change_customer_company1_name1').val(old_id);
        return false;
    });
});


$(function () {
    /*账户*/
    $('body').on('change','.ajax_change_customer_acount1_doing1',function ()
    {
        var obj=$(this).siblings('.action');
        var old_id=obj.find('.cancel-change-doing1').data('doing1'),new_id=$(this).val();
        if(old_id != new_id)
        {
            obj.find('.change-doing1').data('doing1',new_id);
            obj.removeClass('none');
        }
        else
        {
            obj.addClass('none');
        }
    }).on('click','a.change-doing1',function ()
    {
        var $url=this.href,$customer_acount1_doing1=$(this).data('doing1'),$n_id=$(this).data('id');
        var obj=$(this);
        $.post($url,{customer_acount1_doing1:$customer_acount1_doing1,n_id:$n_id}, function (data) {
            if (data.code==1) {
                obj.parent().addClass('none');
                obj.siblings('.cancel-change-doing1').data('doing1',$customer_acount1_doing1);
                layer.msg(data.msg,{icon: 6});
            }else{
                layer.msg(data.msg,{icon: 5});
            }
        }, "json");
        return false;
    }).on('click','a.cancel-change-doing1',function () {
        var old_id=$(this).data('doing1'),obj=$(this).parent();
        obj.addClass('none').siblings('.ajax_change_customer_acount1_doing1').val(old_id);
        return false;
    });
});

$(function () {
    /*行业类别*/
    $('body').on('change','.ajax_change_industry_category_doing3',function ()
    {
        var obj=$(this).siblings('.action');
        var old_id=obj.find('.cancel-change-doing3').data('doing3'),new_id=$(this).val();
        if(old_id != new_id)
        {
            obj.find('.change-doing3').data('doing3',new_id);
            obj.removeClass('none');
        }
        else
        {
            obj.addClass('none');
        }
    }).on('click','a.change-doing3',function ()
    {
        var $url=this.href,$industry_category_doing3=$(this).data('doing3'),$n_id=$(this).data('id');
        var obj=$(this);
        $.post($url,{industry_category_doing3:$industry_category_doing3,n_id:$n_id}, function (data) {
            if (data.code==1) {
                obj.parent().addClass('none');
                obj.siblings('.cancel-change-doing3').data('doing3',$industry_category_doing3);
                layer.msg(data.msg,{icon: 6});
            }else{
                layer.msg(data.msg,{icon: 5});
            }
        }, "json");
        return false;
    }).on('click','a.cancel-change-doing3',function () {
        var old_id=$(this).data('doing3'),obj=$(this).parent();
        obj.addClass('none').siblings('.ajax_change_industry_category_doing3').val(old_id);
        return false;
    });
});




/* 导出 */
$(function () {
    $('body').on('click','.excel_runexportedit-btn',function ()
    {
        var $url = this.href,
            val = $(this).data('id');

        //alert($url);
        //alert(val);


        $.post($url, {excel_runexport_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit").show(300);
                $("#editexcel_runexport_id").val(data.excel_runexport_id);
                $("#editexcel_runexport_name").val(data.excel_runexport_name);
                $("#editexcel_runexport_explain").val(data.excel_runexport_explain);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});



//打印数组js函数：
function writeObj(obj){
    var description = "";
    for(var i in obj){
        var property=obj[i];
        description+=i+" = "+property+"\n";
    }
    alert(description);
}


/* 机构信用等级 */
$(function () {
    $('body').on('click','.cust_leveledit-btn',function ()
    {
        var $url = this.href,
            val = $(this).data('id');

        $.post($url, {cust_level_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit").show(300);
                $("#editcust_level_id").val(data.cust_level_id);
                $("#editcust_level_name").val(data.cust_level_name);
                $("#editcust_level_explain").val(data.cust_level_explain);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});
//机构信用等级
function cust_level_add(val) {
    $('#news_cust_level').val(val);
}


$(function () {
    /*客户级别*/
    $('body').on('change','.ajax_change_cust_level_info',function ()
    {
        var obj=$(this).siblings('.action');
        var old_id=obj.find('.cancel-change-info').data('info'),new_id=$(this).val();
        if(old_id != new_id)
        {
            obj.find('.change-info').data('info',new_id);
            obj.removeClass('none');
        }
        else
        {
            obj.addClass('none');
        }
    }).on('click','a.change-info',function ()
    {
        var $url=this.href,$cust_level_info=$(this).data('info'),$n_id=$(this).data('id');
        var obj=$(this);
        $.post($url,{cust_level_info:$cust_level_info,n_id:$n_id}, function (data) {
            if (data.code==1) {
                obj.parent().addClass('none');
                obj.siblings('.cancel-change-info').data('info',$cust_level_info);
                layer.msg(data.msg,{icon: 6});
            }else{
                layer.msg(data.msg,{icon: 5});
            }
        }, "json");
        return false;
    }).on('click','a.cancel-change-info',function () {
        var old_id=$(this).data('info'),obj=$(this).parent();
        obj.addClass('none').siblings('.ajax_change_cust_level_info').val(old_id);
        return false;
    });
});


$(function () {
    /*客户所属公司*/
    $('body').on('change','.ajax_change_cust_company_from',function ()
    {
        var obj=$(this).siblings('.action');
        var old_id=obj.find('.cancel-change-ifrom').data('ifrom'),new_id=$(this).val();
        if(old_id != new_id)
        {
            obj.find('.change-ifrom').data('ifrom',new_id);
            obj.removeClass('none');
        }
        else
        {
            obj.addClass('none');
        }
    }).on('click','a.change-ifrom',function ()
    {
        var $url=this.href,$cust_company_from=$(this).data('ifrom'),$n_id=$(this).data('id');
        var obj=$(this);
        $.post($url,{cust_company_from:$cust_company_from,n_id:$n_id}, function (data) {
            if (data.code==1) {
                obj.parent().addClass('none');
                obj.siblings('.cancel-change-ifrom').data('ifrom',$cust_company_from);
                layer.msg(data.msg,{icon: 6});
            }else{
                layer.msg(data.msg,{icon: 5});
            }
        }, "json");
        return false;
    }).on('click','a.cancel-change-ifrom',function () {
        var old_id=$(this).data('ifrom'),obj=$(this).parent();
        obj.addClass('none').siblings('.ajax_change_cust_company_from').val(old_id);
        return false;
    });
});



$(function ()
{
    /*服务项目管理*/
    $('body').on('click','.rule-list001',function ()
    {
        var $a=$(this),$tr=$a.parents('tr');
        var $pid=$tr.attr('id');
        
        if($a.find('span').hasClass('fa-minus'))
        {
            //$("tr[id^='"+$pid+"-']").attr('style','display:none');
            $("tr[id^='"+$pid+"-']").attr('style','display:none');
            $a.find('span').removeClass('fa-minus').addClass('fa-plus');
        }
        else
        {
            if($("tr[id^='"+$pid+"-']").length>0)
            {
                $("tr[id^='"+$pid+"-']").attr('style','');
                $a.find('span').removeClass('fa-plus').addClass('fa-minus');
            }
            else
            {
                var $url = this.href,$id=$a.data('id'),$level=$a.data('level');
                $.post($url,{pid:$id,level:$level,id:$pid}, function (data) {
                    if (data) {
                        $a.find('span').removeClass('fa-plus').addClass('fa-minus');
                        $tr.after(data);
                    }else{
                        $a.find('span').removeClass('fa-plus').addClass('fa-minus');
                    }
                }, "json");
            }
        }
        return false;
    });
});



/* 回款项目是否发布 */
$(function () {
    $('body').on('click','.display-btn2',function () {
        var $url = this.href,
            val = $(this).data('id'),
            $btn=$(this);

        $.post($url, {x: val}, function (data)
        {
            if (data.code==1)
            {
                if (data.msg == 0)
                {
                    var a = '<button class="btn btn-minier btn-danger">审核中</button>';
                    $btn.children('div').html(a).attr('title','已显示');
                    return false;
                }
                else
                {
                    var b = '<button class="btn btn-minier btn-yellow">审核通过</button>';
                    $btn.children('div').html(b).attr('title','已隐藏');
                    return false;
                }
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});


/* 行业类别修改 */
$(function () {
    $('body').on('click','.industry_categoryedit-btn',function ()
    {
        var $url = this.href,
            val = $(this).data('id');
        //alert($url);
        $.post($url, {industry_category_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit").show(300);
                $("#editindustry_category_name").val(data.industry_category_name);
                $("#editindustry_category_explain").val(data.industry_category_explain);
                $("#editindustry_category_id").val(data.industry_category_id);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});



/* 沟通详情 */
$(function () {
    $('body').on('click','.cust_communication_details',function ()
    {
        var $url = this.href,
            val = $(this).data('id');
        $.post($url, {back_payment_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit1").show(300);
                $("#edit_cust_id").val(data.cust_follow_id);
                $("#back_payment_detail2").html(data.content);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});


/* 客户详情 */
$(function () {
    $('body').on('click','.cust_communication_details_2',function ()
    {
        var $url = this.href,
            val = $(this).data('id');
        $.post($url, {back_payment_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit1").show(300);
                $("#edit_cust_id").val(data.cust_follow_id);
                $("#back_payment_detail2").html(data.content);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});



/* 沟通详情 */
$(function () {
    $('body').on('click','.cust_communication_details_21',function ()
    {
        var $url = this.href,
            val = $(this).data('id');
        $.post($url, {back_payment_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit21").show(300);
                $("#back_payment_detail21").html(data.content);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});


/* 跟进列表 展示  */
$(function () {
    $('body').on('click','.cust_cust_belong',function ()
    {
        var $url = this.href,
            val = $(this).data('id');
        $.post($url, {back_payment_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit21").show(300);
                $("#back_payment_detail21").html(data.content);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});



//信用级别
/* 机构信用级别 */
$(function () {
    $('body').on('click','.cust_jibieedit-btn',function ()
    {
        var $url = this.href,
            val = $(this).data('id');

        $.post($url, {cust_jibie_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit").show(300);
                $("#editcust_jibie_id").val(data.cust_jibie_id);
                $("#editcust_jibie_name").val(data.cust_jibie_name);
                $("#editcust_jibie_explain").val(data.cust_jibie_explain);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});



/* 部门管理 */
$(function () {
    $('body').on('click','.departmentedit-btn',function ()
    {
        var $url = this.href,
            val = $(this).data('id');
        $.post($url, {id: val}, function (data)
        {
            if (data.code == 1) {
                $("#myModaledit").show(300);
                $("#editid").val(data.id);
                $("#editname").val(data.name);
                $("#editdepartment_order").val(data.department_order);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});
//部门管理详情表
function department_add(val) {
    $('#news_department').val(val);
}




/* 会员登录详情 */
$(function () {
    $('body').on('click','.admin_login-info',function ()
    {
        var $url = this.href,
            val = $(this).data('id');

        $.post($url, {company_id: val}, function (data)
        {
            if (data.code == 1)
            {
                $("#myModaledit2").show(300);
                $("#back_payment_detail2").html(data.content);
            } else {
                layer.alert(data.msg, {icon: 5});
            }
        }, "json");
        return false;
    });
});