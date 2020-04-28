# swoole-mqtt-server


MQTT介绍 https://www.runoob.com/w3cnote/mqtt-intro.html

qos解析 https://www.jianshu.com/p/8b0291e8ee02

匹配规则：

主题层级分隔符  /:

     用于分割主题层级，/分割后的主题，这是消息主题层级设计中很重要的符号。
     比方说： aaa/bbb和  aaa/bbb/ccc 和aaa/bbb/ccc/ddd  ，这样的消息主题格式，
     是一个层层递进的关系，可通过多层通配符同时匹配两者，或者单层通配符只匹配一个。
     这在现实场景中，可以应用到：公司的部门层级推送、国家城市层级推送等包含层级关系的场景。
     
单层通配符  +:

     单层通配符只能匹配一层主题。
     比如：   aaa/+     可以匹配 aaa/bbb ，但是不能匹配aaa/bbb/ccc。
     单独的+号可以匹配单层的所有推送
     
多层通配符  #：

     多层通配符可以匹配于多层主题。
     比如: aaa/#   不但可以匹配aaa/bbb，还可以匹配aaa/bbb/ccc/ddd。  
     也就是说，多层通配符可以匹配符合通配符之前主题层级的所有子集主题。
     单独的#匹配所有的消息主题.


###### 注:   单层通配符和多层通配符只能用于订阅(subscribe)消息而不能用于发布(publish)消息，主题层级分隔符两种情况下均可使用。

######可以使用MQTT客户端测试
######MQTTfx 下载地址：http://mqttfx.bceapp.com/