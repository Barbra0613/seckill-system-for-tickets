# 购票秒杀系统
本购票秒杀系统基于ThinkPHP实现，分为用户端以及后台管理端两部分，系统功能架构如下：
![Image text](https://github.com/Barbra0613/seckill-system-for-tickets/blob/main/pic/系统功能架构.png)


### 用户端
实现高并发下的秒杀任务，项目存储于client文件夹下

### 后台管理端
实现购票秒杀系统的后台管理任务，项目存储于background文件夹下

### 部署说明
1. 用户端：
    - requirements：Nginx 1.18.0、 MySQL 5.6.50、 PHP 5.6、 Redis 6.0.9、 Rabbit MQ、 JWT
      
    - 基于ThinkPHP 5.0部署基础站点
    
    - 将application与public两目录导入站点
    
    - 在站点目录下，配置安装tp5扩展Rabbit MQ、 JWT
    
    - 导入mysql.sql数据库信息
    
    - 配置client/application/database.php中数据库基本信息
    
    - 配置client/application/admin/controller/Index.php中第125行与143行Rabbit MQ用户名、密码信息（默认Rabbit MQ端口号为5672）
    
    - 访问url：域名/index.php/admin/login/index.html 即可进入用户端登录入口
    
2. 后台管理端：
    - requirements：Apache 2.4.46、 MySQL 5.6.50、 PHP 5.6 、 JWT
      
    - 基于ThinkPHP 5.0部署基础站点
    
    - 将application与public两目录导入站点
    
    - 在站点目录下，配置安装tp5扩展JWT
    
    - 导入mysql.sql数据库信息
    
    - 配置background/application/database.php中数据库基本信息
    
    - 访问url：域名index.php/admin/login/index.html 即可进入后台管理端登录入口

