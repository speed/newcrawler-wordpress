<style>
    .postbox h3 {
        font-family: Georgia, "Times New Roman", "Bitstream Charter", Times, serif;
        font-size: 15px;
        padding: 10px 10px;
        margin: 0;
        line-height: 1;
    }
    #ta-table td{
        height:30px;
    }
</style>
<script type="text/javascript">
    jQuery(function ($) {
        $("h3.hndle").click(function () {
            $(this).next(".inside").slideToggle('fast');
        });
    });
</script>

<?php
$basicWebAddress = str_replace('\\','/',$_SERVER['HTTP_HOST'].str_replace('/wp-admin', '', dirname($_SERVER['SCRIPT_NAME'])));

if (isset($_POST['submit1']) && $_POST['submit1'] != '') {
    update_option('ta_password', $_POST['ta_password']);

    echo '<div id="message" class="updated fade"><p>更新成功！</p></div>';
}
?>
<div class="wrap">
    <h2>鸟巢采集器</h2>
    <br/>


    <form id="myform" method="post" action="admin.php?page=targetany/ta-article-setting.php">
        <div class="postbox">
            <h3 class="hndle" style="cursor:pointer;">发布设置</h3>
            <div class="inside">
                <table width="100%" id="ta-table">
                    <tr>
                        <td width="15%">插件版本:</td>
                        <td>鸟巢采集器WordPress发布插件 v3.1.3</td>

                    <span class="gray">( https://github.com/speed/newcrawler-wordpress )</span>
                    </tr>
                    <tr>
                        <td width="10%">官网地址:</td>
                        <td><a href="http://www.newcrawler.com" target="_blank">www.newcrawler.com</a></td>
                    </tr>

                    <?php $ta_password = get_option('ta_password', "NewCrawler"); ?>
                    <tr>
                        <td width="10%">发布密码:</td>
                        <td><input type="text" name="ta_password" style="color:black;width:300px" value="<?php echo $ta_password; ?>" />
                            <span class="gray">( 建议修改成自己的常用密码，并填入鸟巢采集器导出到 WordPress 选项中的发布密码输入框内 )</span>
                        </td>
                    </tr>
                    <tr>
                        <td width="10%">您网站访问地址为:</td>
                        <td><input type="text" name="ta_web" disabled="disabled" readonly="readonly" style="width:300px" value="<?php echo $basicWebAddress;?>" />
                            <span class="gray">( 此地址为您网站访问地址，您可以复制该链接并填入鸟巢采集器导出到 WordPress 发布接口配置中的发布网址输入框内)</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="submit" class="button-primary"  name="submit1"  value="保存更改" />
                        </td>
                        <td>
                           
                        </td>
                    </tr>
                </table>
            </div>
    </form>
</div>
</div>