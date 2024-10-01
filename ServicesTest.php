<?php
use PHPUnit\Framework\TestCase;

final class ServicesTest extends TestCase {
    public function testCreateEntry() {
        $service = new Services();
        $this->assertEquals("200", $service->createEntry("gina", "gina perez", "perez", "gina@mail.com", "ginaP4ss"));
    }

    public function testReadEntry() {
        $service = new Services();
        $response = $service->readEntry("gina");
        $this->assertEquals("200", $response['status']);
    }

    public function testUpdatePassword() {
        $service = new Services();
        $this->assertEquals("200", $service->updatePassword("gina", "ginaP4ssw0rd"));
    }

    public function testAuthenticate() {
        $service = new Services();
        $this->assertEquals("200", $service->authenticate("gina", "ginaP4ssw0rd"));
    }

    public function testDeleteEntry() {
        $service = new Services();
        $this->assertEquals("200", $service->deleteEntry("gina"));
    }
}
?>