
var Light = Class.create({
    // Constructor
    initialize: function(position, color, intensity) {  
        this.position = position;
		this.lookAt = new THREE.Vector3(0.0, 0.0, 0.0);
		this.color = color;
		this.intensity = intensity;
		this.projectionMatrix;
		this.viewMatrix;
		this.up = new THREE.Vector3(0.0, 1.0, 0.0);
    },
	setPosition: function(position) {
		this.position = position;
	},
	getPosition: function() {
		return this.position;
	},
	setColor: function(color) {
		this.color = color;
	},
	getColor: function() {
		return this.color;
	},
	setIntensity: function(intensity) {
		this.intensity = intensity;
	},
	getIntensity: function() {
		return this.intensity;
	},
	setLookAt: function(x, y, z) {
		this.lookAt = new THREE.Vector3(x, y, z);
	},
	setLookAtV: function(target) {
		this.lookAt = target;
	},
	getLookAt: function() {
		return this.lookAt;
	},
	generateViewMatrix: function() {
		/*var zAxis = this.lookAt - this.position;
		zAxis.normalize();
		var xAxis = new THREE.vec3(0.0, 0.0, 0.0);
		this.up.normalize();
		xAxis.crossVectors(this.up, zAxis);
		var yAxis = new THREE.vec3(0.0, 0.0, 0.0);
		yAxis.crossVectors(zAxis, xAxis);*/
		this.viewMatrix = new THREE.Matrix4();
		this.viewMatrix.lookAt(this.position, this.lookAt, this.up);
		return this.viewMatrix;
	},
	getViewMatrix: function() {
		if (this.viewMatrix == null)
			return this.generateViewMatrix();
		return this.viewMatrix;
	},
	generateProjectionMatrix: function() {
		this.projectionMatrix = new THREE.Matrix4();
		this.projectionMatrix.makePerspective(45, 1.0, 0.1, 1000.0);
		return this.projectionMatrix;
	},
	getProjectionMatrix: function() {
		if (this.projectionMatrix == null)
			return this.generateProjectionMatrix();
		return this.projectionMatrix;
	}
});
