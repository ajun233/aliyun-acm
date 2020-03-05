# aliyun-acm
A PHP client for Aliyun ACM


---
### 使用

#### 初始化

```		
$acm_client = new Client("acm.aliyun.com", 8080);
$acm_client->setAccessKey("your accessKey");
$acm_client->setSecretKey("your secretKey");
	
$acm_client->getServerList();
$acm_client->refreshServerList();
```

#### 获取配置

```
$acm_client->setNameSpace($namespace);

$value = $acm_client->getConfig($dataid,  $group ? : "DEFAULT_GROUP");
```

#### 更新配置

```
$acm_client->setNameSpace($namespace);
		
$acm_client->publishConfig($dataid,  $group ? : "DEFAULT_GROUP",  $config_value);
```

---
### `endpoint`

`endpoint`可查阅[阿里云`ACM`文档](https://help.aliyun.com/document_detail/64129.html?spm=a2c4g.11186623.2.8.734e2f427vpzZX)获得





